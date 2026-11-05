<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 100 products, 100 customers and 100 orders (plus categories, brands, shipping) so the storefront and every
 * admin screen can be tried with real-looking data.   php artisan db:seed --class=DemoRecordsSeeder
 * Run it again to add another batch. (The small per-business sample set is separate: nexcart:demo.)
 */
class DemoRecordsSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(crc32((string) microtime(true)));
        $pick = fn (array $a) => $a[array_rand($a)];

        DB::transaction(function () use ($pick) {
            $cats = collect(['Electronics', 'Fashion', 'Home & Kitchen', 'Beauty & Care', 'Groceries', 'Sports & Outdoors', 'Toys & Kids', 'Books & Stationery'])
                ->map(fn ($n) => Category::firstOrCreate(['name' => $n], ['is_active' => true]));
            $brands = collect(['Nexa', 'Urban Co', 'Zenith', 'Orbit', 'Lumina', 'Prime Choice'])->map(fn ($n) => Brand::firstOrCreate(['name' => $n], ['is_active' => true]));

            if (! ShippingZone::exists()) {
                $z = ShippingZone::create(['name' => 'Everywhere', 'cities' => null, 'is_active' => true]);
                $z->rates()->create(['name' => 'Standard delivery', 'price' => 200, 'free_over' => 5000, 'eta' => '2-4 days', 'is_active' => true]);
                $z->rates()->create(['name' => 'Express delivery', 'price' => 450, 'eta' => 'Next day', 'is_active' => true]);
            }

            $nouns = ['Headphones', 'Backpack', 'Sneakers', 'Blender', 'Lamp', 'Notebook', 'Water Bottle', 'Watch', 'Sunglasses', 'Mug', 'Speaker', 'Jacket', 'Face Wash', 'Perfume', 'Rice 5kg', 'Olive Oil', 'Yoga Mat', 'Football', 'Puzzle', 'Pen Set', 'Cushion', 'Kettle', 'Charger', 'T-Shirt', 'Cap'];
            $adjs = ['Classic', 'Pro', 'Ultra', 'Eco', 'Smart', 'Premium', 'Mini', 'Max', 'Daily', 'Royal'];

            $products = collect();
            for ($i = 1; $i <= 100; $i++) {
                $price = $pick([199, 349, 499, 799, 999, 1499, 1999, 2999, 4999, 7999]) + mt_rand(0, 9) * 10;
                $variable = $i % 10 === 0;
                $p = new Product;
                $p->forceFill([
                    'category_id' => $cats->random()->id, 'brand_id' => $brands->random()->id,
                    'name' => $pick($adjs).' '.$pick($nouns).' '.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'sku' => 'DEMO-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
                    'type' => $variable ? 'variable' : 'simple', 'status' => 'active',
                    'short_description' => 'A great everyday pick — sample product for testing.',
                    'description' => 'Sample product '.$i.'. Replace it with your real catalogue. Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
                    'price' => $price, 'compare_price' => $i % 4 === 0 ? round($price * 1.25, 0) : null, 'cost' => round($price * 0.6, 2),
                    'track_stock' => true, 'stock_qty' => $variable ? 0 : mt_rand(0, 80), 'low_stock_threshold' => 5, 'is_featured' => $i % 7 === 0,
                ])->save();
                if ($variable) {
                    foreach (['Small', 'Medium', 'Large'] as $k => $size) {
                        $p->variants()->create(['name' => $size, 'sku' => $p->sku.'-'.($k + 1), 'price' => $price + $k * 100, 'stock_qty' => mt_rand(0, 30), 'is_active' => true, 'sort_order' => $k]);
                    }
                    $p->update(['price' => $price]);
                }
                $products->push($p->fresh('variants'));
            }

            $first = ['Ali', 'Sara', 'Ahmed', 'Fatima', 'Usman', 'Ayesha', 'Hassan', 'Zainab', 'Bilal', 'Hina', 'Omar', 'Maryam', 'Hamza', 'Noor', 'Talha', 'Iqra', 'Daniyal', 'Sana', 'Imran', 'Laiba'];
            $last = ['Khan', 'Ahmed', 'Malik', 'Sheikh', 'Butt', 'Raza', 'Chaudhry', 'Qureshi', 'Siddiqui', 'Ansari'];
            $cities = ['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta'];

            $hash = \Illuminate\Support\Facades\Hash::make('Demo-pass-123');   // hashed once: 100 slow bcrypts would take half a minute
            $customers = collect();
            for ($i = 1; $i <= 100; $i++) {
                $name = $pick($first).' '.$pick($last);
                $u = User::create(['name' => $name, 'email' => 'customer'.$i.'.'.Str::lower(Str::random(4)).'@demo.test', 'phone' => '03'.mt_rand(0, 4).mt_rand(1000000, 9999999), 'password' => $hash,
                    'default_address' => ['address_line' => 'House '.mt_rand(1, 300).', Street '.mt_rand(1, 40), 'city' => $pick($cities)]]);
                $u->forceFill(['email_verified_at' => now()])->save();
                $customers->push($u);
            }

            $taxRate = max(0.0, (float) setting('store.tax_rate', 0));
            $inclusive = (bool) setting('store.tax_inclusive', false);
            $statuses = ['completed', 'completed', 'completed', 'completed', 'completed', 'confirmed', 'preparing', 'out_for_delivery', 'pending', 'pending', 'cancelled', 'refunded'];
            for ($i = 1; $i <= 100; $i++) {
                $c = $customers->random();
                $status = $pick($statuses);
                $placed = now()->subDays(mt_rand(0, 59))->subMinutes(mt_rand(0, 1380));
                $lines = [];
                $subtotal = 0.0;
                foreach ($products->random(mt_rand(1, 4)) as $p) {
                    $variant = $p->isVariable() ? $p->variants->random() : null;
                    $unit = (float) ($variant?->price ?? $p->price);
                    $qty = mt_rand(1, 3);
                    $lines[] = compact('p', 'variant', 'unit', 'qty');
                    $subtotal += round($unit * $qty, 2);
                }
                $subtotal = round($subtotal, 2);
                $shipping = $subtotal >= 5000 ? 0.0 : 200.0;
                $tax = round($inclusive ? $subtotal - $subtotal / (1 + $taxRate / 100) : $subtotal * $taxRate / 100, 2);
                $total = round($subtotal + $shipping + ($inclusive ? 0 : $tax), 2);
                $paid = in_array($status, ['completed', 'refunded'], true);

                $o = Order::forceCreate([
                    'number' => 'TMP-'.Str::random(10), 'public_token' => Order::newToken(), 'user_id' => $c->id, 'status' => $status,
                    'payment_status' => $status === 'refunded' ? 'refunded' : ($paid ? 'paid' : 'unpaid'), 'payment_method' => 'cod', 'shipping_method' => 'Standard delivery', 'is_pickup' => false,
                    'name' => $c->name, 'email' => $c->email, 'phone' => $c->phone, 'address_line' => $c->default_address['address_line'], 'city' => $c->default_address['city'],
                    'subtotal' => $subtotal, 'discount' => 0, 'shipping_cost' => $shipping, 'tax' => $tax, 'total' => $total, 'tax_rate' => $taxRate, 'tax_inclusive' => $inclusive,
                    'currency' => (string) setting('store.currency', 'PKR'), 'placed_at' => $placed, 'paid_at' => $paid ? $placed : null,
                    'completed_at' => in_array($status, ['completed', 'refunded'], true) ? $placed->copy()->addHours(mt_rand(2, 48)) : null,
                    'cancelled_at' => $status === 'cancelled' ? $placed->copy()->addHours(2) : null, 'locale' => 'en', 'created_at' => $placed, 'updated_at' => $placed,
                ]);
                $o->update(['number' => Order::numberFor($o->id)]);
                foreach ($lines as $l) {
                    OrderItem::create(['order_id' => $o->id, 'product_id' => $l['p']->id, 'variant_id' => $l['variant']?->id, 'name' => $l['p']->name, 'variant_name' => $l['variant']?->name,
                        'sku' => $l['variant']?->sku ?: $l['p']->sku, 'unit_price' => $l['unit'], 'qty' => $l['qty'], 'total' => round($l['unit'] * $l['qty'], 2)]);
                }
            }
        });

        $this->command?->info('Added 100 products, 100 customers and 100 orders (every demo customer signs in with password Demo-pass-123).');
    }
}
