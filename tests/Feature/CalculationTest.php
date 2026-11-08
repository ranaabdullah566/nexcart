<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PricingService;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Hand-computed expectations: every number below was worked out on paper first. */
class CalculationTest extends TestCase
{
    use RefreshDatabase;

    private $std;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        Mail::fake();
        $z = ShippingZone::create(['name' => 'All', 'cities' => null]);
        $this->std = $z->rates()->create(['name' => 'Std', 'price' => 150, 'free_over' => 2000]);
    }

    private function q(float $subtotal, ?Coupon $c = null): array
    {
        return app(PricingService::class)->quote($subtotal, $c, $this->std);
    }

    public function test_exclusive_tax_is_charged_after_discount_and_not_on_shipping(): void
    {
        setting()->setMany(['store.tax_rate' => 16, 'store.tax_inclusive' => false]);
        $c = Coupon::create(['code' => 'TEN', 'type' => 'percent', 'value' => 10, 'is_active' => true]);
        $q = $this->q(1000, $c);
        // discount 100 → taxable 900 → tax 144 → 900 + 144 + shipping 150
        $this->assertEquals([100, 144, 150, 1194], [$q['discount'], $q['tax'], $q['shipping'], $q['total']]);
    }

    public function test_inclusive_tax_is_extracted_not_added(): void
    {
        setting()->setMany(['store.tax_rate' => 16, 'store.tax_inclusive' => true]);
        $q = $this->q(1160);
        // tax inside 1160 at 16% = 160 ; total = 1160 + 150 shipping
        $this->assertEquals([160, 1310], [$q['tax'], $q['total']]);
        $q = $this->q(900, Coupon::create(['code' => 'X', 'type' => 'fixed', 'value' => 100, 'is_active' => true]));
        // taxable 800 → 800 - 800/1.16 = 110.34
        $this->assertEquals([110.34, 950], [$q['tax'], $q['total']]);
    }

    public function test_rounding_never_drifts_by_a_cent(): void
    {
        setting()->setMany(['store.tax_rate' => 7.5, 'store.tax_inclusive' => false]);
        $q = $this->q(33.33);
        $this->assertEquals(2.5, $q['tax']);
        $this->assertEqualsWithDelta(185.83, $q['total'], 0.001);   // 2.49975 → 2.50
        // total must always equal the parts the customer sees
        foreach ([0.01, 9.99, 19.95, 123.45, 999.99, 1234.56] as $s) {
            $q = $this->q($s);
            $this->assertEqualsWithDelta($q['subtotal'] - $q['discount'] + $q['shipping'] + $q['tax'], $q['total'], 0.001, "subtotal {$s}");
        }
    }

    public function test_discounts_never_push_the_total_negative(): void
    {
        setting()->setMany(['store.tax_rate' => 16, 'store.tax_inclusive' => false]);
        $fixed = Coupon::create(['code' => 'BIG', 'type' => 'fixed', 'value' => 99999, 'is_active' => true]);
        $q = $this->q(500, $fixed);
        $this->assertEquals([500, 0, 0, 150], [$q['discount'], $q['tax'], $q['subtotal'] - $q['discount'], $q['total']]);
        $pct = Coupon::create(['code' => 'CAP', 'type' => 'percent', 'value' => 250, 'is_active' => true]);   // >100% is clamped
        $this->assertEquals(500, $this->q(500, $pct)['discount']);
        $capped = Coupon::create(['code' => 'C2', 'type' => 'percent', 'value' => 50, 'max_discount' => 120, 'is_active' => true]);
        $this->assertEquals(120, $this->q(1000, $capped)['discount']);
    }

    public function test_free_shipping_rules(): void
    {
        setting()->setMany(['store.tax_rate' => 0]);
        $this->assertEquals(150, $this->q(1999.99)['shipping']);
        $this->assertEquals(0, $this->q(2000)['shipping']);          // threshold is inclusive
        $fs = Coupon::create(['code' => 'FS', 'type' => 'free_shipping', 'value' => 0, 'is_active' => true]);
        $q = $this->q(100, $fs);
        $this->assertEquals([0, 0, 100], [$q['discount'], $q['shipping'], $q['total']]);
    }

    private function order(Product $p, int $qty, ?string $coupon = null, ?User $user = null): Order
    {
        $r = $this->actingAs($user ?? User::factory()->create())->postJson('/cart/add', ['product_id' => $p->id, 'qty' => $qty]);
        $r->assertOk();
        $this->post('/checkout', ['name' => 'A', 'email' => 'a@x.test', 'phone' => '0300 1234567', 'address_line' => 'x', 'city' => 'Lahore', 'shipping_rate_id' => $this->std->id, 'payment_method' => 'cod', 'coupon' => $coupon])->assertSessionHasNoErrors();

        return Order::latest('id')->firstOrFail();
    }

    public function test_placed_order_adds_up_and_cancelling_gives_back_stock_and_coupon(): void
    {
        setting()->setMany(['store.tax_rate' => 16, 'store.tax_inclusive' => false]);
        $p = new Product;
        $p->forceFill(['name' => 'W', 'price' => 333.33, 'status' => 'active', 'track_stock' => true, 'stock_qty' => 10])->save();
        $c = Coupon::create(['code' => 'ONCE', 'type' => 'percent', 'value' => 15, 'usage_limit' => 1, 'is_active' => true]);

        $o = $this->order($p, 3, 'ONCE');
        // 3 × 333.33 = 999.99 ; −15% = 150 (149.9985) ; taxable 849.99 ; tax 136 (135.9984) ; ship 150
        $this->assertEquals(999.99, $o->subtotal);
        $this->assertEquals(150.00, $o->discount);
        $this->assertEquals(136.00, $o->tax);
        $this->assertEquals(1135.99, $o->total);
        $this->assertEquals(round($o->items->sum('total'), 2), (float) $o->subtotal);
        $this->assertEquals(7, $p->fresh()->stock_qty);
        $this->assertSame(1, $c->fresh()->used_count);
        $this->assertMatchesRegularExpression('/^BUR?-?\d+$|^[A-Z]{1,3}-\d{6}$/', $o->number);
        $this->assertSame(Order::numberFor($o->id), $o->number);

        $admin = tap(User::factory()->create(), fn ($u) => $u->assignRole('owner'));
        app(OrderService::class)->transition($o->fresh(), 'cancelled', $admin);
        $this->assertEquals(10, $p->fresh()->stock_qty);
        $this->assertSame(0, $c->fresh()->used_count);   // the single-use code works again
    }

    public function test_order_numbers_are_unique_and_sequential(): void
    {
        $p = new Product;
        $p->forceFill(['name' => 'W', 'price' => 10, 'status' => 'active', 'track_stock' => false])->save();
        $a = $this->order($p, 1);
        $b = $this->order($p, 1);
        $this->assertNotSame($a->number, $b->number);
        $this->assertSame($b->id, $a->id + 1);
    }

    public function test_dashboard_days_follow_the_store_timezone(): void
    {
        setting()->set('store.timezone', 'Asia/Karachi');   // UTC+5
        $owner = tap(User::factory()->create(), fn ($u) => $u->assignRole('owner'));
        $nowKhi = now('Asia/Karachi');
        // 01:00 Karachi today = 20:00 UTC yesterday: it is still "today" for the shop
        $placed = $nowKhi->copy()->startOfDay()->addHour();
        if ($placed->gt($nowKhi)) {
            $this->markTestSkipped('Runs only after 01:00 store time.');
        }
        Order::forceCreate(['number' => 'T-1', 'public_token' => 'x', 'status' => 'pending', 'payment_status' => 'unpaid', 'payment_method' => 'cod', 'name' => 'A', 'email' => 'a@x', 'phone' => '1', 'shipping_method' => 's', 'subtotal' => 10, 'discount' => 0, 'shipping_cost' => 0, 'tax' => 0, 'total' => 10, 'tax_rate' => 0, 'currency' => 'PKR', 'placed_at' => $placed->copy()->utc()]);
        $r = $this->actingAs($owner)->get('/admin');
        $r->assertOk();
        $this->assertSame(1, $r->viewData('sales')['today']);
        $this->assertSame(10.0, (float) end($r->viewData('chart')['revenue']));
    }
}
