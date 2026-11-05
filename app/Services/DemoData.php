<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Product;
use App\Support\BusinessProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Sample catalogue for each business type, so a new store (or a sales demo) looks alive in one click.
 * Everything it creates is remembered by id, so "remove" deletes exactly that and never real products.
 */
class DemoData
{
    public function __construct(private StockService $stock) {}

    public function loaded(): bool
    {
        return (bool) setting('demo.ids');
    }

    /** @return int number of products created */
    public function load(string $type): int
    {
        if ($this->loaded()) {
            throw new RuntimeException(__('Sample data is already loaded. Remove it first to load it again.'));
        }
        $set = $this->dataset($type);
        $ids = ['products' => [], 'categories' => [], 'brands' => [], 'groups' => []];

        DB::transaction(function () use ($set, $type, &$ids) {
            $cats = [];
            foreach ($set['categories'] as $key => $name) {
                $cats[$key] = Category::create(['name' => $name, 'is_active' => true, 'sort_order' => count($cats)]);
                $ids['categories'][] = $cats[$key]->id;
            }
            $brands = [];
            foreach ($set['brands'] ?? [] as $name) {
                $brands[$name] = Brand::create(['name' => $name, 'is_active' => true]);
                $ids['brands'][] = $brands[$name]->id;
            }
            $groups = [];
            foreach ($set['groups'] ?? [] as $key => $g) {
                $group = ModifierGroup::create(['name' => $g['name'], 'min_select' => $g['min'], 'max_select' => $g['max'], 'is_active' => true]);
                foreach ($g['options'] as $i => [$label, $delta]) {
                    $group->options()->create(['name' => $label, 'price_delta' => $delta, 'sort_order' => $i]);
                }
                $groups[$key] = $group;
                $ids['groups'][] = $group->id;
            }

            foreach ($set['products'] as $i => $d) {
                $p = new Product;
                $p->forceFill([
                    'category_id' => $cats[$d['cat']]->id, 'brand_id' => isset($d['brand']) ? $brands[$d['brand']]->id : null,
                    'name' => $d['name'], 'sku' => 'DEMO-'.strtoupper(substr($type, 0, 3)).'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'type' => isset($d['variants']) ? 'variable' : 'simple', 'status' => 'active', 'short_description' => $d['short'] ?? null, 'description' => $d['desc'] ?? ($d['short'] ?? null),
                    'price' => isset($d['variants']) ? min(array_column($d['variants'], 1)) : $d['price'], 'compare_price' => $d['compare'] ?? null,
                    'track_stock' => true, 'stock_qty' => 0, 'low_stock_threshold' => $d['low'] ?? 5, 'requires_prescription' => $d['rx'] ?? false,
                    'is_featured' => $d['featured'] ?? false, 'specs' => $d['specs'] ?? null,
                ])->save();
                $ids['products'][] = $p->id;

                foreach ($d['variants'] ?? [] as $v => [$label, $price, $qty]) {
                    $variant = $p->variants()->create(['name' => $label, 'price' => $price, 'stock_qty' => 0, 'sort_order' => $v, 'sku' => $p->sku.'-'.($v + 1)]);
                    $this->receive($p, $qty, $variant, $d);
                }
                if (! isset($d['variants'])) {
                    $this->receive($p, $d['stock'] ?? 25, null, $d);
                }
                foreach ($d['groups'] ?? [] as $g) {
                    $p->modifierGroups()->attach($groups[$g]->id);
                }
                $this->attachImage($p, $d['hue'] ?? ($i * 47) % 360, $d['name']);
            }
        });

        setting()->set('demo.ids', $ids);

        return count($ids['products']);
    }

    /** @return int number of products removed */
    public function remove(): int
    {
        $ids = (array) setting('demo.ids');
        $n = 0;

        DB::transaction(function () use ($ids, &$n) {
            foreach (Product::withTrashed()->whereIn('id', $ids['products'] ?? [])->get() as $p) {
                $p->forceDelete();   // also deletes its images
                $n++;
            }
            // shared things are only removed when nothing real hangs on them
            Category::whereIn('id', $ids['categories'] ?? [])->get()->each(fn ($c) => $c->products()->exists() || $c->children()->exists() ? null : $c->delete());
            Brand::whereIn('id', $ids['brands'] ?? [])->get()->each(fn ($b) => $b->products()->exists() ? null : $b->delete());
            ModifierGroup::whereIn('id', $ids['groups'] ?? [])->get()->each(fn ($g) => $g->products()->exists() ? null : $g->delete());
        });

        setting()->set('demo.ids', null);

        return $n;
    }

    private function receive(Product $p, int $qty, $variant, array $d): void
    {
        if ($qty < 1) {
            return;
        }
        $batch = BusinessProfile::hasModule('batch_expiry')
            ? ['batch_no' => 'B'.date('ym').'-'.$p->id, 'expires_at' => now()->addDays($d['expires_in'] ?? 400)->toDateString()]
            : [];
        $this->stock->adjust($p, $qty, 'received', $variant, $batch, __('Sample stock'));
    }

    /** A generated gradient picture — no downloads, no licensing questions. */
    private function attachImage(Product $p, int $hue, string $seed): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }
        $size = 640;
        $im = imagecreatetruecolor($size, $size);
        [$r1, $g1, $b1] = $this->hsv($hue, .55, .95);
        [$r2, $g2, $b2] = $this->hsv(($hue + 50) % 360, .65, .55);
        for ($y = 0; $y < $size; $y++) {
            $t = $y / $size;
            imageline($im, 0, $y, $size, $y, imagecolorallocate($im, (int) ($r1 + ($r2 - $r1) * $t), (int) ($g1 + ($g2 - $g1) * $t), (int) ($b1 + ($b2 - $b1) * $t)));
        }
        imagealphablending($im, true);
        mt_srand(crc32($seed));
        for ($k = 0; $k < 4; $k++) {
            $d = mt_rand(140, 380);
            imagefilledellipse($im, mt_rand(60, $size - 60), mt_rand(60, $size - 60), $d, $d, imagecolorallocatealpha($im, 255, 255, 255, mt_rand(104, 116)));
        }
        imagefilledellipse($im, (int) ($size / 2), (int) ($size / 2), 230, 230, imagecolorallocatealpha($im, 255, 255, 255, 40));
        imagefilledellipse($im, (int) ($size / 2), (int) ($size / 2), 170, 170, imagecolorallocatealpha($im, $r2, $g2, $b2, 40));

        File::ensureDirectoryExists($dir = storage_path('app/tmp'));
        $file = $dir.'/demo-'.$p->id.'.png';
        imagepng($im, $file);
        imagedestroy($im);

        $p->addMedia($file)->toMediaCollection('images');   // moves the temp file
    }

    private function hsv(int $h, float $s, float $v): array
    {
        $i = (int) floor($h / 60) % 6;
        $f = $h / 60 - floor($h / 60);
        [$p, $q, $t] = [$v * (1 - $s), $v * (1 - $f * $s), $v * (1 - (1 - $f) * $s)];
        [$r, $g, $b] = [[$v, $t, $p], [$q, $v, $p], [$p, $v, $t], [$p, $q, $v], [$t, $p, $v], [$v, $p, $q]][$i];

        return [(int) ($r * 255), (int) ($g * 255), (int) ($b * 255)];
    }

    private function dataset(string $type): array
    {
        return match ($type) {
            'fastfood' => [
                'categories' => ['burgers' => 'Burgers', 'pizza' => 'Pizza', 'sides' => 'Sides', 'drinks' => 'Drinks'],
                'brands' => ['House Kitchen'],
                'groups' => [
                    'extras' => ['name' => 'Extras', 'min' => 0, 'max' => 3, 'options' => [['Extra cheese', 80], ['Jalapeños', 40], ['Crispy bacon', 120], ['Garlic mayo', 30]]],
                    'size' => ['name' => 'Choose size', 'min' => 1, 'max' => 1, 'options' => [['Regular', 0], ['Large', 150]]],
                ],
                'products' => [
                    ['name' => 'Zinger Burger', 'cat' => 'burgers', 'brand' => 'House Kitchen', 'price' => 590, 'compare' => 690, 'featured' => true, 'short' => 'Crispy fried chicken, spicy mayo and lettuce.', 'specs' => ['spice_level' => 'Hot', 'prep_time_min' => 12, 'is_veg' => false], 'groups' => ['extras'], 'hue' => 18],
                    ['name' => 'Smash Beef Burger', 'cat' => 'burgers', 'brand' => 'House Kitchen', 'price' => 690, 'featured' => true, 'short' => 'Two smashed patties, cheddar, pickles and house sauce.', 'specs' => ['spice_level' => 'Mild', 'prep_time_min' => 14, 'is_veg' => false], 'groups' => ['extras'], 'hue' => 8],
                    ['name' => 'Garden Veggie Burger', 'cat' => 'burgers', 'price' => 520, 'short' => 'Crunchy veggie patty with avocado mayo.', 'specs' => ['spice_level' => 'Mild', 'prep_time_min' => 10, 'is_veg' => true], 'groups' => ['extras'], 'hue' => 110],
                    ['name' => 'Margherita Pizza', 'cat' => 'pizza', 'short' => 'Stone-baked with mozzarella and basil.', 'variants' => [['Small', 650, 30], ['Medium', 1050, 30], ['Large', 1450, 30]], 'specs' => ['spice_level' => 'Mild', 'prep_time_min' => 18, 'is_veg' => true], 'groups' => ['extras'], 'hue' => 0, 'featured' => true],
                    ['name' => 'Fajita Pizza', 'cat' => 'pizza', 'short' => 'Chicken fajita, peppers and onions.', 'variants' => [['Small', 750, 30], ['Medium', 1200, 30], ['Large', 1650, 30]], 'specs' => ['spice_level' => 'Medium', 'prep_time_min' => 20, 'is_veg' => false], 'groups' => ['extras'], 'hue' => 35],
                    ['name' => 'Loaded Fries', 'cat' => 'sides', 'price' => 350, 'short' => 'Fries with cheese sauce and jalapeños.', 'specs' => ['spice_level' => 'Medium', 'prep_time_min' => 8, 'is_veg' => true], 'hue' => 48],
                    ['name' => 'Peri Peri Wings', 'cat' => 'sides', 'price' => 450, 'short' => 'Six wings tossed in peri peri.', 'specs' => ['spice_level' => 'Extra hot', 'prep_time_min' => 12, 'is_veg' => false], 'hue' => 12],
                    ['name' => 'Cola 500ml', 'cat' => 'drinks', 'price' => 120, 'short' => 'Ice cold.', 'stock' => 60, 'hue' => 210],
                    ['name' => 'Oreo Shake', 'cat' => 'drinks', 'price' => 380, 'short' => 'Thick, creamy and crunchy.', 'specs' => ['prep_time_min' => 5, 'is_veg' => true], 'hue' => 280],
                ],
            ],
            'pharmacy' => [
                'categories' => ['pain' => 'Pain & fever', 'vit' => 'Vitamins & supplements', 'baby' => 'Baby care', 'rx' => 'Prescription medicines'],
                'brands' => ['GSK', 'Getz Pharma', 'Searle'],
                'products' => [
                    ['name' => 'Panadol 500mg (20 tablets)', 'cat' => 'pain', 'brand' => 'GSK', 'price' => 45, 'featured' => true, 'short' => 'Paracetamol for pain and fever.', 'specs' => ['composition' => 'Paracetamol 500mg', 'dosage_form' => 'Tablet', 'strength' => '500mg', 'manufacturer' => 'GSK'], 'stock' => 120, 'hue' => 205],
                    ['name' => 'Brufen 400mg (30 tablets)', 'cat' => 'pain', 'price' => 190, 'short' => 'Ibuprofen anti-inflammatory.', 'specs' => ['composition' => 'Ibuprofen 400mg', 'dosage_form' => 'Tablet', 'strength' => '400mg', 'manufacturer' => 'Abbott'], 'stock' => 80, 'expires_in' => 40, 'hue' => 25],
                    ['name' => 'Vitamin C 1000mg (20 effervescent)', 'cat' => 'vit', 'brand' => 'Getz Pharma', 'price' => 280, 'compare' => 340, 'featured' => true, 'short' => 'Daily immune support.', 'specs' => ['composition' => 'Ascorbic acid 1000mg', 'dosage_form' => 'Tablet', 'strength' => '1000mg', 'manufacturer' => 'Getz Pharma'], 'stock' => 60, 'hue' => 40],
                    ['name' => 'Multivitamin Daily', 'cat' => 'vit', 'price' => 950, 'short' => '30-day multivitamin and mineral.', 'specs' => ['composition' => 'Vitamins A–E, zinc, iron', 'dosage_form' => 'Capsule', 'strength' => 'Daily', 'manufacturer' => 'Searle'], 'stock' => 35, 'low' => 10, 'hue' => 330],
                    ['name' => 'Baby Gripe Water 120ml', 'cat' => 'baby', 'price' => 160, 'short' => 'Gentle relief for infant colic.', 'specs' => ['composition' => 'Dill oil, sodium bicarbonate', 'dosage_form' => 'Syrup', 'strength' => '—', 'manufacturer' => 'Woodward'], 'stock' => 45, 'hue' => 170],
                    ['name' => 'Amoxicillin 500mg (12 capsules)', 'cat' => 'rx', 'price' => 320, 'rx' => true, 'short' => 'Antibiotic — prescription required.', 'specs' => ['composition' => 'Amoxicillin 500mg', 'dosage_form' => 'Capsule', 'strength' => '500mg', 'manufacturer' => 'Searle'], 'stock' => 50, 'expires_in' => 300, 'hue' => 350],
                    ['name' => 'Augmentin 625mg (14 tablets)', 'cat' => 'rx', 'price' => 780, 'rx' => true, 'short' => 'Antibiotic — prescription required.', 'specs' => ['composition' => 'Amoxicillin + clavulanic acid', 'dosage_form' => 'Tablet', 'strength' => '625mg', 'manufacturer' => 'GSK'], 'stock' => 3, 'low' => 5, 'hue' => 300],
                ],
            ],
            'medical_store' => [
                'categories' => ['monitor' => 'Monitors & testers', 'mobility' => 'Mobility aids', 'care' => 'Daily care & PPE'],
                'brands' => ['Omron', 'Accu-Chek'],
                'products' => [
                    ['name' => 'Digital BP Monitor', 'cat' => 'monitor', 'brand' => 'Omron', 'price' => 8900, 'compare' => 9800, 'featured' => true, 'short' => 'Upper-arm automatic blood pressure monitor.', 'specs' => ['model_no' => 'HEM-7120', 'warranty_months' => 24], 'stock' => 15, 'hue' => 200],
                    ['name' => 'Glucometer Kit', 'cat' => 'monitor', 'brand' => 'Accu-Chek', 'price' => 4200, 'short' => 'Meter, lancets and 10 strips.', 'specs' => ['model_no' => 'Active', 'warranty_months' => 12], 'stock' => 20, 'hue' => 150],
                    ['name' => 'Infrared Thermometer', 'cat' => 'monitor', 'price' => 3500, 'short' => 'Non-contact, 1-second reading.', 'specs' => ['model_no' => 'IR-200', 'warranty_months' => 12], 'stock' => 30, 'hue' => 260],
                    ['name' => 'Foldable Wheelchair', 'cat' => 'mobility', 'price' => 24500, 'featured' => true, 'short' => 'Lightweight steel frame with footrests.', 'specs' => ['model_no' => 'WC-310', 'warranty_months' => 12], 'stock' => 4, 'low' => 2, 'hue' => 20],
                    ['name' => 'Walking Stick (adjustable)', 'cat' => 'mobility', 'price' => 1800, 'short' => 'Aluminium, non-slip rubber tip.', 'specs' => ['model_no' => 'WS-12', 'warranty_months' => 6], 'stock' => 25, 'hue' => 80],
                    ['name' => 'Surgical Masks (box of 50)', 'cat' => 'care', 'price' => 450, 'short' => '3-ply disposable masks.', 'stock' => 100, 'hue' => 190],
                    ['name' => 'Nitrile Gloves (box of 100)', 'cat' => 'care', 'price' => 1200, 'variants' => [['Small', 1200, 40], ['Medium', 1200, 40], ['Large', 1250, 40]], 'hue' => 230],
                ],
            ],
            default => [
                'categories' => ['fashion' => 'Fashion', 'tech' => 'Electronics', 'home' => 'Home & living'],
                'brands' => ['Nova', 'Urban Co'],
                'products' => [
                    ['name' => 'Classic Cotton T-Shirt', 'cat' => 'fashion', 'brand' => 'Urban Co', 'short' => 'Soft 100% cotton tee.', 'variants' => [['S', 1500, 25], ['M', 1500, 40], ['L', 1500, 40], ['XL', 1600, 15]], 'featured' => true, 'hue' => 210],
                    ['name' => 'Denim Jacket', 'cat' => 'fashion', 'brand' => 'Urban Co', 'price' => 6500, 'compare' => 7900, 'short' => 'Relaxed fit, stone wash.', 'stock' => 18, 'hue' => 225],
                    ['name' => 'Wireless Earbuds', 'cat' => 'tech', 'brand' => 'Nova', 'price' => 4500, 'featured' => true, 'short' => '24-hour battery, noise reduction.', 'stock' => 40, 'hue' => 270],
                    ['name' => 'Smart Watch X', 'cat' => 'tech', 'brand' => 'Nova', 'price' => 8900, 'compare' => 10500, 'short' => 'AMOLED display, heart-rate, 7-day battery.', 'stock' => 22, 'hue' => 190],
                    ['name' => 'Power Bank 20,000mAh', 'cat' => 'tech', 'brand' => 'Nova', 'price' => 3200, 'short' => 'Fast charging, dual USB.', 'stock' => 55, 'hue' => 150],
                    ['name' => 'Ceramic Desk Lamp', 'cat' => 'home', 'price' => 2600, 'short' => 'Warm light, touch dimmer.', 'stock' => 30, 'hue' => 40],
                    ['name' => 'Scented Candle Set', 'cat' => 'home', 'price' => 1400, 'short' => 'Three soy candles in a gift box.', 'stock' => 60, 'hue' => 330],
                ],
            ],
        };
    }
}
