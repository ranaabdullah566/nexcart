<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\BusinessProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Sample medicine catalogue, so a new pharmacy (or a sales demo) looks alive in one click.
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
    public function load(string $type = 'pharmacy'): int
    {
        if ($this->loaded()) {
            throw new RuntimeException(__('Sample data is already loaded. Remove it first to load it again.'));
        }
        $set = $this->dataset();
        $ids = ['products' => [], 'categories' => [], 'brands' => []];

        DB::transaction(function () use ($set, &$ids) {
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
            foreach ($set['products'] as $i => $d) {
                $p = new Product;
                $p->forceFill([
                    'category_id' => $cats[$d['cat']]->id, 'brand_id' => isset($d['brand']) ? $brands[$d['brand']]->id : null,
                    'name' => $d['name'], 'sku' => 'DEMO-PHA-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
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

    private function dataset(): array
    {
        $m = fn (string $comp, string $form, string $str, string $maker) => ['composition' => $comp, 'dosage_form' => $form, 'strength' => $str, 'manufacturer' => $maker];

        return [
            'categories' => ['pain' => 'Pain & fever', 'cold' => 'Cold, flu & allergy', 'stomach' => 'Stomach & digestion', 'vit' => 'Vitamins & supplements', 'diabetes' => 'Diabetes care', 'baby' => 'Baby & mother care', 'skin' => 'Skin care', 'devices' => 'Health devices', 'rx' => 'Prescription medicines'],
            'brands' => ['GSK', 'Getz Pharma', 'Searle', 'Abbott', 'Omron'],
            'products' => [
                ['name' => 'Panadol 500mg (20 tablets)', 'cat' => 'pain', 'brand' => 'GSK', 'price' => 45, 'featured' => true, 'short' => 'Paracetamol for pain and fever.', 'specs' => $m('Paracetamol 500mg', 'Tablet', '500mg', 'GSK'), 'stock' => 120, 'hue' => 205],
                ['name' => 'Brufen 400mg', 'cat' => 'pain', 'brand' => 'Abbott', 'short' => 'Ibuprofen anti-inflammatory.', 'variants' => [['10 tablets', 70, 60], ['30 tablets', 190, 60]], 'specs' => $m('Ibuprofen 400mg', 'Tablet', '400mg', 'Abbott'), 'expires_in' => 40, 'hue' => 25],
                ['name' => 'Calpol Suspension 120ml', 'cat' => 'pain', 'brand' => 'GSK', 'price' => 160, 'short' => 'Paracetamol syrup for children.', 'specs' => $m('Paracetamol 120mg/5ml', 'Syrup', '120mg/5ml', 'GSK'), 'stock' => 50, 'hue' => 190],
                ['name' => 'Disprin (10 tablets)', 'cat' => 'pain', 'price' => 30, 'short' => 'Soluble aspirin.', 'specs' => $m('Aspirin 300mg', 'Tablet', '300mg', 'Reckitt'), 'stock' => 90, 'hue' => 15],
                ['name' => 'Arinac Forte (12 tablets)', 'cat' => 'cold', 'price' => 140, 'short' => 'Cold, flu and congestion relief.', 'specs' => $m('Ibuprofen + pseudoephedrine', 'Tablet', '200mg/30mg', 'Abbott'), 'stock' => 70, 'hue' => 160],
                ['name' => 'Avil 25mg (20 tablets)', 'cat' => 'cold', 'price' => 85, 'short' => 'Antihistamine for allergy relief.', 'specs' => $m('Pheniramine 25mg', 'Tablet', '25mg', 'Sanofi'), 'stock' => 65, 'hue' => 120],
                ['name' => 'Cough Syrup 100ml', 'cat' => 'cold', 'price' => 180, 'short' => 'Soothing syrup for dry cough.', 'specs' => $m('Dextromethorphan', 'Syrup', '15mg/5ml', 'Searle'), 'stock' => 40, 'hue' => 30],
                ['name' => 'Gaviscon Liquid 150ml', 'cat' => 'stomach', 'price' => 620, 'short' => 'Fast relief from heartburn and acidity.', 'specs' => $m('Sodium alginate', 'Syrup', '500mg/10ml', 'Reckitt'), 'stock' => 30, 'hue' => 95],
                ['name' => 'ORS Sachets (10 pack)', 'cat' => 'stomach', 'price' => 120, 'short' => 'Oral rehydration salts.', 'specs' => $m('Glucose + electrolytes', 'Other', '—', 'Searle'), 'stock' => 100, 'hue' => 175],
                ['name' => 'Vitamin C 1000mg (20 effervescent)', 'cat' => 'vit', 'brand' => 'Getz Pharma', 'price' => 280, 'compare' => 340, 'featured' => true, 'short' => 'Daily immune support.', 'specs' => $m('Ascorbic acid 1000mg', 'Tablet', '1000mg', 'Getz Pharma'), 'stock' => 60, 'hue' => 40],
                ['name' => 'Multivitamin Daily', 'cat' => 'vit', 'brand' => 'Searle', 'short' => 'Multivitamin and mineral.', 'variants' => [['30 capsules', 950, 35], ['60 capsules', 1700, 25]], 'specs' => $m('Vitamins A–E, zinc, iron', 'Capsule', 'Daily', 'Searle'), 'low' => 10, 'hue' => 330],
                ['name' => 'Calcium + D3 (30 tablets)', 'cat' => 'vit', 'price' => 690, 'short' => 'Bone and joint support.', 'specs' => $m('Calcium 500mg + D3', 'Tablet', '500mg', 'Getz Pharma'), 'stock' => 40, 'hue' => 55],
                ['name' => 'Glucometer Kit', 'cat' => 'diabetes', 'price' => 4200, 'featured' => true, 'short' => 'Meter, lancets and 10 test strips.', 'specs' => $m('—', 'Other', '—', 'Accu-Chek'), 'stock' => 20, 'hue' => 150],
                ['name' => 'Glucose Test Strips (50)', 'cat' => 'diabetes', 'price' => 2300, 'short' => 'Compatible with the Glucometer Kit.', 'specs' => $m('—', 'Other', '—', 'Accu-Chek'), 'stock' => 35, 'expires_in' => 200, 'hue' => 140],
                ['name' => 'Baby Gripe Water 120ml', 'cat' => 'baby', 'price' => 160, 'short' => 'Gentle relief for infant colic.', 'specs' => $m('Dill oil, sodium bicarbonate', 'Syrup', '—', 'Woodward'), 'stock' => 45, 'hue' => 170],
                ['name' => 'Baby Rash Cream 50g', 'cat' => 'baby', 'price' => 340, 'short' => 'Protects against diaper rash.', 'specs' => $m('Zinc oxide', 'Cream / Ointment', '—', 'Sudocrem'), 'stock' => 38, 'hue' => 320],
                ['name' => 'Betnovate-N Cream 15g', 'cat' => 'skin', 'price' => 110, 'short' => 'Antibiotic and steroid skin cream.', 'specs' => $m('Betamethasone + neomycin', 'Cream / Ointment', '0.1%', 'GSK'), 'stock' => 55, 'hue' => 345],
                ['name' => 'Sunblock SPF 50 (60ml)', 'cat' => 'skin', 'price' => 1450, 'short' => 'Broad-spectrum sun protection.', 'specs' => $m('—', 'Cream / Ointment', 'SPF 50', 'Local'), 'stock' => 25, 'hue' => 45],
                ['name' => 'Digital BP Monitor', 'cat' => 'devices', 'brand' => 'Omron', 'price' => 8900, 'compare' => 9800, 'featured' => true, 'short' => 'Upper-arm automatic blood pressure monitor.', 'specs' => $m('—', 'Other', '—', 'Omron'), 'stock' => 15, 'hue' => 200],
                ['name' => 'Infrared Thermometer', 'cat' => 'devices', 'price' => 3500, 'short' => 'Non-contact, 1-second reading.', 'specs' => $m('—', 'Other', '—', 'Local'), 'stock' => 30, 'hue' => 260],
                ['name' => 'Surgical Masks (box of 50)', 'cat' => 'devices', 'price' => 450, 'short' => '3-ply disposable masks.', 'specs' => $m('—', 'Other', '—', 'Local'), 'stock' => 100, 'hue' => 190],
                ['name' => 'Amoxicillin 500mg (12 capsules)', 'cat' => 'rx', 'brand' => 'Searle', 'price' => 320, 'rx' => true, 'short' => 'Antibiotic — prescription required.', 'specs' => $m('Amoxicillin 500mg', 'Capsule', '500mg', 'Searle'), 'stock' => 50, 'expires_in' => 300, 'hue' => 350],
                ['name' => 'Augmentin 625mg (14 tablets)', 'cat' => 'rx', 'brand' => 'GSK', 'price' => 780, 'rx' => true, 'short' => 'Antibiotic — prescription required.', 'specs' => $m('Amoxicillin + clavulanic acid', 'Tablet', '625mg', 'GSK'), 'stock' => 3, 'low' => 5, 'hue' => 300],
                ['name' => 'Metformin 500mg (30 tablets)', 'cat' => 'rx', 'price' => 95, 'rx' => true, 'short' => 'For type 2 diabetes — prescription required.', 'specs' => $m('Metformin 500mg', 'Tablet', '500mg', 'Getz Pharma'), 'stock' => 80, 'hue' => 20],
            ],
        ];
    }
}
