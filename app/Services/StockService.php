<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Support\BusinessProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place that changes stock. Every change writes a StockMovement (audit trail).
 * With the batch_expiry module on, received stock needs a batch and removals use first-expiry-first-out.
 */
class StockService
{
    /**
     * @param  array{batch_no?:string, expires_at?:?string, cost?:?float}  $batch  required when receiving with batch_expiry
     */
    public function adjust(Product $product, int $change, string $reason, ?ProductVariant $variant = null, array $batch = [], ?string $note = null, ?int $userId = null): void
    {
        if ($change === 0) {
            throw ValidationException::withMessages(['change' => __('Quantity cannot be zero.')]);
        }
        if (! $product->track_stock) {
            throw ValidationException::withMessages(['product_id' => __('Stock tracking is off for this product.')]);
        }
        if ($product->isVariable() && ! $variant) {
            throw ValidationException::withMessages(['variant_id' => __('Choose a variant.')]);
        }
        if ($variant && $variant->product_id !== $product->id) {
            throw ValidationException::withMessages(['variant_id' => __('Variant does not belong to this product.')]);
        }

        DB::transaction(function () use ($product, $change, $reason, $variant, $batch, $note, $userId) {
            $target = $variant
                ? ProductVariant::whereKey($variant->id)->lockForUpdate()->first()
                : Product::whereKey($product->id)->lockForUpdate()->first();

            if ($target->stock_qty + $change < 0) {
                throw ValidationException::withMessages(['change' => __('Not enough stock. Available: :n', ['n' => $target->stock_qty])]);
            }

            $useBatches = BusinessProfile::hasModule('batch_expiry');

            if ($useBatches && $change > 0) {
                $this->receiveIntoBatch($product, $variant, $change, $batch, $reason, $note, $userId, $target);
            } elseif ($useBatches) {
                $this->consumeFefo($product, $variant, -$change, $reason, $note, $userId, $target);
            } else {
                $target->stock_qty += $change;
                $target->save();
                $this->log($product, $variant, null, $change, $target->stock_qty, $reason, $note, $userId);
            }
        });
    }

    private function receiveIntoBatch(Product $product, ?ProductVariant $variant, int $qty, array $batch, string $reason, ?string $note, ?int $userId, $target): void
    {
        $batchNo = trim((string) ($batch['batch_no'] ?? ''));
        if ($batchNo === '') {
            throw ValidationException::withMessages(['batch_no' => __('Batch number is required.')]);
        }

        $row = ProductBatch::where('product_id', $product->id)->where('variant_id', $variant?->id)->where('batch_no', $batchNo)->first()
            ?? new ProductBatch(['product_id' => $product->id, 'variant_id' => $variant?->id, 'batch_no' => $batchNo, 'qty' => 0]);

        $row->expires_at = $batch['expires_at'] ?? $row->expires_at;
        $row->cost = $batch['cost'] ?? $row->cost;
        $row->qty += $qty;
        $row->save();

        $target->stock_qty += $qty;
        $target->save();
        $this->log($product, $variant, $row->id, $qty, $target->stock_qty, $reason, $note, $userId);
    }

    private function consumeFefo(Product $product, ?ProductVariant $variant, int $qty, string $reason, ?string $note, ?int $userId, $target): void
    {
        $batches = ProductBatch::where('product_id', $product->id)->where('variant_id', $variant?->id)->where('qty', '>', 0)
            ->orderByRaw('expires_at is null')->orderBy('expires_at')->orderBy('id')->lockForUpdate()->get();

        $remaining = $qty;
        foreach ($batches as $row) {
            if ($remaining === 0) {
                break;
            }
            $take = min($row->qty, $remaining);
            $row->decrement('qty', $take);
            $remaining -= $take;
            $target->stock_qty -= $take;
            $target->save();
            $this->log($product, $variant, $row->id, -$take, $target->stock_qty, $reason, $note, $userId);
        }

        if ($remaining > 0) {
            // stock existed before batches were switched on: take the rest without a batch
            $target->stock_qty -= $remaining;
            $target->save();
            $this->log($product, $variant, null, -$remaining, $target->stock_qty, $reason, $note, $userId);
        }
    }

    private function log(Product $product, ?ProductVariant $variant, ?int $batchId, int $change, int $balance, string $reason, ?string $note, ?int $userId): void
    {
        StockMovement::create([
            'product_id' => $product->id, 'variant_id' => $variant?->id, 'batch_id' => $batchId, 'user_id' => $userId,
            'change' => $change, 'balance' => $balance, 'reason' => $reason, 'note' => $note,
        ]);
    }
}
