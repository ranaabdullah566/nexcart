<?php

namespace App\Support;

use App\Models\DeliverySlot;
use App\Models\ShippingZone;

/** Sensible first-run data so a brand-new store can take an order right away. Everything is editable in the admin. */
class StarterData
{
    public static function seed(string $type): void
    {
        $modules = BusinessProfile::find($type)['modules'];

        if (! ShippingZone::query()->exists()) {
            $zone = ShippingZone::create(['name' => 'All areas', 'cities' => null, 'sort_order' => 0]);
            $zone->rates()->create(['name' => 'Standard delivery', 'price' => 150, 'free_over' => 3000, 'eta' => in_array('kitchen_display', $modules, true) ? '30–45 min' : '2–3 days', 'eta_minutes' => in_array('kitchen_display', $modules, true) ? 40 : null, 'sort_order' => 0]);
            if (in_array('pickup', $modules, true)) {
                $zone->rates()->create(['name' => 'Pick up from store', 'is_pickup' => true, 'eta' => '15–20 min', 'eta_minutes' => 20, 'sort_order' => 1]);
            }
        }

        if (in_array('delivery_slots', $modules, true) && ! DeliverySlot::query()->exists()) {
            foreach ([['Morning', '09:00', '12:00'], ['Afternoon', '12:00', '16:00'], ['Evening', '16:00', '20:00']] as $i => [$label, $from, $to]) {
                DeliverySlot::create(['label' => $label, 'starts_at' => $from, 'ends_at' => $to, 'sort_order' => $i]);
            }
        }
    }
}
