<?php

namespace App\Support;

use App\Models\DeliverySlot;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\ShippingZone;

/** Sensible first-run data so a brand-new store can take an order right away. Everything is editable in the admin. */
class StarterData
{
    public static function seed(string $type): void
    {
        $modules = BusinessProfile::find($type)['modules'];

        if (! ShippingZone::query()->exists()) {
            $zone = ShippingZone::create(['name' => 'All areas', 'cities' => null, 'sort_order' => 0]);
            $zone->rates()->create(['name' => 'Standard delivery', 'price' => 150, 'free_over' => 3000, 'eta' => '1–2 days', 'sort_order' => 0]);
            if (in_array('pickup', $modules, true)) {
                $zone->rates()->create(['name' => 'Pick up from store', 'is_pickup' => true, 'eta' => '15–20 min', 'eta_minutes' => 20, 'sort_order' => 1]);
            }
        }

        if (! Page::query()->exists()) {
            $pages = [
                ['About us', 'Tell customers who you are, what you sell and why they can trust you.', ['ur' => ['title' => 'ہمارے بارے میں', 'body' => 'گاہکوں کو بتائیں کہ آپ کون ہیں، کیا بیچتے ہیں اور آپ پر بھروسہ کیوں کیا جائے۔'], 'ur_Latn' => ['title' => 'Hamare baare mein', 'body' => 'Customers ko batayein ke aap kaun hain, kya bechte hain aur aap par bharosa kyun kiya jaye.']]],
                ['Delivery & returns', 'Explain how long delivery takes, what it costs and how returns or exchanges work.', ['ur' => ['title' => 'ڈیلیوری اور واپسی', 'body' => 'بتائیں کہ ڈیلیوری میں کتنا وقت لگتا ہے، خرچ کیا ہے اور واپسی یا تبدیلی کیسے ہوتی ہے۔'], 'ur_Latn' => ['title' => 'Delivery aur wapsi', 'body' => 'Batayein ke delivery mein kitna waqt lagta hai, kharch kya hai aur wapsi ya tabdeeli kaise hoti hai.']]],
                ['Privacy policy', 'Explain what customer information you collect and how you use it.', ['ur' => ['title' => 'پرائیویسی پالیسی', 'body' => 'بتائیں کہ آپ گاہکوں کی کون سی معلومات لیتے ہیں اور انہیں کیسے استعمال کرتے ہیں۔'], 'ur_Latn' => ['title' => 'Privacy policy', 'body' => 'Batayein ke aap customers ki kaun si maloomat lete hain aur unhein kaise istemal karte hain.']]],
            ];
            foreach ($pages as $i => [$title, $body, $tr]) {
                $page = new Page(['title' => $title, 'body' => $body, 'is_published' => true]);
                $page->translations = $tr;
                $page->save();
                $item = new MenuItem(['location' => 'footer', 'label' => $title, 'type' => 'page', 'page_id' => $page->id, 'sort_order' => $i, 'is_active' => true]);
                $item->translations = ['ur' => ['label' => $tr['ur']['title']], 'ur_Latn' => ['label' => $tr['ur_Latn']['title']]];
                $item->save();
            }
        }

        if (in_array('delivery_slots', $modules, true) && ! DeliverySlot::query()->exists()) {
            foreach ([['Morning', '09:00', '12:00'], ['Afternoon', '12:00', '16:00'], ['Evening', '16:00', '20:00']] as $i => [$label, $from, $to]) {
                DeliverySlot::create(['label' => $label, 'starts_at' => $from, 'ends_at' => $to, 'sort_order' => $i]);
            }
        }
    }
}
