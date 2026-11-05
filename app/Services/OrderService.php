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

        // medicines that need a prescription are never prepared or dispatched before a pharmacist approves it
        if ($order->awaitsPrescription() && in_array($to, ['preparing', 'ready', 'out_for_delivery', 'completed'], true)) {
            throw ValidationException::withMessages(['status' => __('Verify the prescription before preparing this order.')]);
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

    public function assignRider(Order $order, ?User $rider, ?User $by = null): void
    {
        if ($rider && ! $rider->hasRole('rider')) {
            throw ValidationException::withMessages(['rider_id' => __('That person is not a rider.')]);
        }
        if ($order->is_pickup || ! $order->isOpen()) {
            throw ValidationException::withMessages(['rider_id' => __('This order cannot be assigned to a rider.')]);
        }

        $order->update(['rider_id' => $rider?->id, 'rider_assigned_at' => $rider ? now() : null]);
        $this->log($order, 'rider', null, $rider ? __('Assigned to :name', ['name' => $rider->name]) : __('Rider removed'), $by);
    }

    public function reviewPrescription(Order $order, string $decision, ?string $note, User $by): void
    {
        if (! $order->requires_prescription || ! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => __('This order has no prescription to review.')]);
        }
        if ($decision === 'rejected' && blank($note)) {
            throw ValidationException::withMessages(['note' => __('Tell the customer why the prescription was rejected.')]);
        }

        $order->update([
            'prescription_status' => $decision, 'prescription_note' => $note,
            'prescription_reviewed_by' => $by->id, 'prescription_reviewed_at' => now(),
        ]);
        $this->log($order, 'prescription', $decision, $note, $by);
    }

    /** Customer sends a new file after a rejection: goes back to the pharmacist's queue. */
    public function resubmitPrescription(Order $order): void
    {
        $order->update(['prescription_status' => 'pending', 'prescription_note' => null, 'prescription_reviewed_by' => null, 'prescription_reviewed_at' => null]);
        $this->log($order, 'prescription', 'pending', __('New prescription uploaded'));
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
