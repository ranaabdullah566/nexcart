<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private StockService $stock) {}

    public function log(Order $order, string $type, ?string $status = null, ?string $note = null, ?User $by = null): void
    {
        OrderEvent::create(['order_id' => $order->id, 'user_id' => $by?->id, 'type' => $type, 'status' => $status, 'note' => $note]);
    }

    public function transition(Order $order, string $to, ?User $by = null, ?string $note = null): void
    {
        if (! in_array($to, $order->nextStatuses(), true)) {
            throw ValidationException::withMessages(['status' => __('This order cannot move from :from to :to.', ['from' => $order->statusLabel(), 'to' => __(str_replace('_', ' ', ucfirst($to)))])]);
        }

        DB::transaction(function () use ($order, $to, $by, $note) {
            $order->status = $to;

            if ($to === 'completed') {
                $order->completed_at = now();
                // cash is collected at the door, so a completed cash order is a paid order
                if ($order->payment_status === 'unpaid' && $order->payment_method === 'cod') {
                    $order->payment_status = 'paid';
                    $order->paid_at = now();
                    $this->log($order, 'payment', 'paid', __('Cash collected'), $by);
                }
            }
            if ($to === 'cancelled') {
                $order->cancelled_at = now();
                $this->restock($order, $by);
            }
            if ($to === 'refunded') {
                $order->payment_status = 'refunded';
            }
            $order->save();

            $this->log($order, 'status', $to, $note, $by);
        });
    }

    public function markPaid(Order $order, ?string $reference = null, ?User $by = null): void
    {
        if ($order->payment_status === 'paid') {
            return;
        }
        $order->update(['payment_status' => 'paid', 'paid_at' => now(), 'payment_reference' => $reference ?? $order->payment_reference]);
        $this->log($order, 'payment', 'paid', $reference, $by);
    }

    /** Put sold stock back (cancelled orders). */
    private function restock(Order $order, ?User $by): void
    {
        foreach ($order->items()->with('product.variants')->get() as $item) {
            $product = $item->product;
            if (! $product || ! $product->track_stock) {
                continue;
            }
            $variant = $item->variant_id ? $product->variants->firstWhere('id', $item->variant_id) : null;
            if ($product->isVariable() && ! $variant) {
                continue;   // variant was deleted since — nothing to put back
            }
            $this->stock->adjust($product, $item->qty, 'return', $variant, ['batch_no' => 'RETURN-'.$order->number], __('Order :number cancelled', ['number' => $order->number]), $by?->id);
        }
    }
}
