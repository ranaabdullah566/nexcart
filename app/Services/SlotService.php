<?php

namespace App\Services;

use App\Models\DeliverySlot;
use App\Models\Order;
use App\Support\BusinessProfile;
use Illuminate\Support\Carbon;

/** Delivery / pickup time windows: which are open today and over the next days, and how many places are left. */
class SlotService
{
    public function enabled(): bool
    {
        return BusinessProfile::hasModule('delivery_slots') && DeliverySlot::where('is_active', true)->exists();
    }

    public function now(): Carbon
    {
        return now(setting('store.timezone', config('app.timezone')));
    }

    public function daysAhead(): int
    {
        return max(0, min(14, (int) setting('delivery.days_ahead', 3)));
    }

    public function cutoffMinutes(): int
    {
        return max(0, (int) setting('delivery.cutoff_minutes', 60));
    }

    /**
     * @return array<int, array{date: string, label: string, slots: array<int, array{id:int,label:string,open:bool,left:?int}>}>
     */
    public function days(): array
    {
        $slots = DeliverySlot::where('is_active', true)->orderBy('sort_order')->orderBy('starts_at')->get();
        $today = $this->now()->startOfDay();
        $days = [];

        for ($i = 0; $i <= $this->daysAhead(); $i++) {
            $date = $today->copy()->addDays($i);
            $rows = [];
            foreach ($slots as $slot) {
                $left = $this->remaining($slot, $date);
                $open = $this->isOpen($slot, $date) && ($left === null || $left > 0);
                $rows[] = ['id' => $slot->id, 'label' => $slot->displayLabel(), 'open' => $open, 'left' => $left];
            }
            $days[] = ['date' => $date->toDateString(), 'label' => $i === 0 ? __('Today') : ($i === 1 ? __('Tomorrow') : $date->translatedFormat('D, d M')), 'slots' => $rows];
        }

        return $days;
    }

    /** A slot can be booked until `cutoff` minutes before it starts. */
    public function isOpen(DeliverySlot $slot, Carbon $date): bool
    {
        return $slot->is_active && $this->now()->lt($slot->startsOn($date)->subMinutes($this->cutoffMinutes()));
    }

    /** Places left on that date (null = unlimited). Cancelled orders free their place. */
    public function remaining(DeliverySlot $slot, Carbon $date): ?int
    {
        if ($slot->capacity === null) {
            return null;
        }
        $taken = Order::where('delivery_slot_id', $slot->id)->whereDate('slot_date', $date->toDateString())->whereNotIn('status', ['cancelled'])->count();

        return max(0, $slot->capacity - $taken);
    }

    /** Validate a chosen date + slot. Returns the slot, or a message why it cannot be used. */
    public function check(?string $date, ?int $slotId): DeliverySlot|string
    {
        $slot = $slotId ? DeliverySlot::where('is_active', true)->find($slotId) : null;
        if (! $slot || ! $date) {
            return __('Please choose a delivery time.');
        }
        try {
            $d = Carbon::parse($date, setting('store.timezone', config('app.timezone')))->startOfDay();
        } catch (\Throwable) {
            return __('Please choose a delivery time.');
        }
        $today = $this->now()->startOfDay();
        if ($d->lt($today) || $d->gt($today->copy()->addDays($this->daysAhead()))) {
            return __('That day is not available.');
        }
        if (! $this->isOpen($slot, $d)) {
            return __('That time slot has closed. Please pick another.');
        }
        $left = $this->remaining($slot, $d);
        if ($left !== null && $left <= 0) {
            return __('That time slot is full. Please pick another.');
        }

        return $slot;
    }
}
