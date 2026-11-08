<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\Payments\StripeCheckout;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        setting()->set('modules.riders', false);   // direct dispatch flow, no rider hand-off
        Mail::fake();
        $this->owner = tap(User::factory()->create(), fn ($u) => $u->assignRole('owner'));
    }

    private function order(array $o = [], int $stock = 10, int $qty = 2): Order
    {
        $zone = ShippingZone::first() ?? ShippingZone::create(['name' => 'All']);
        $rate = $zone->rates()->first() ?? $zone->rates()->create(['name' => 'Standard', 'price' => 100]);
        $p = new Product;
        $p->forceFill(['name' => 'Widget', 'price' => 500, 'status' => 'active', 'track_stock' => true, 'stock_qty' => $stock])->save();

        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => $qty]);

        $order = app(CheckoutService::class)->place($o + [
            'name' => 'Ali', 'email' => 'ali@example.com', 'phone' => '0300111222', 'address_line' => 'Somewhere', 'city' => 'Karachi',
            'shipping_rate_id' => $rate->id, 'payment_method' => 'cod',
        ]);

        return $order;
    }

    public function test_status_flow_for_delivery_orders_and_cash_collection(): void
    {
        $o = $this->order();
        $svc = app(OrderService::class);

        $this->assertSame(['confirmed', 'cancelled'], $o->nextStatuses());
        foreach (['confirmed', 'preparing', 'out_for_delivery', 'completed'] as $s) {
            $svc->transition($o->fresh(), $s, $this->owner);
        }
        $o->refresh();
        $this->assertSame(['completed', 'paid'], [$o->status, $o->payment_status]);   // cash collected at the door
        $this->assertNotNull($o->completed_at);
        $this->assertSame(['refunded'], $o->nextStatuses());
        $this->assertDatabaseHas('order_events', ['order_id' => $o->id, 'type' => 'payment', 'status' => 'paid']);
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $o = $this->order();
        $this->actingAs($this->owner)->post("/admin/orders/{$o->id}/status", ['status' => 'completed'])->assertSessionHasErrors('status');
        $this->assertSame('pending', $o->fresh()->status);
        $this->post("/admin/orders/{$o->id}/status", ['status' => 'ready'])->assertSessionHasErrors('status');   // delivery orders do not use "ready"
    }

    public function test_cancelling_puts_stock_back(): void
    {
        $o = $this->order([], stock: 10, qty: 4);
        $p = Product::first();
        $this->assertSame(6, $p->stock_qty);

        $this->actingAs($this->owner)->post("/admin/orders/{$o->id}/status", ['status' => 'cancelled', 'note' => 'customer asked'])->assertRedirect('/admin/orders');
        $this->assertSame(10, $p->fresh()->stock_qty);
        $this->assertSame('cancelled', $o->fresh()->status);
        $this->assertNotNull($o->fresh()->cancelled_at);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $p->id, 'change' => 4, 'reason' => 'return']);

        $this->post("/admin/orders/{$o->id}/status", ['status' => 'confirmed'])->assertSessionHasErrors('status');   // final state
    }

    public function test_mark_paid_and_refund_permissions(): void
    {
        $o = $this->order(['payment_method' => 'cod']);
        $this->actingAs($this->owner)->post("/admin/orders/{$o->id}/paid", ['reference' => 'TXN-1'])->assertRedirect();
        $this->assertSame(['paid', 'TXN-1'], [$o->fresh()->payment_status, $o->fresh()->payment_reference]);

        $svc = app(OrderService::class);
        foreach (['confirmed', 'preparing', 'out_for_delivery', 'completed'] as $s) {
            $svc->transition($o->fresh(), $s, $this->owner);
        }

        $staff = tap(User::factory()->create(), fn ($u) => $u->assignRole('staff'));
        $this->actingAs($staff)->post("/admin/orders/{$o->id}/status", ['status' => 'refunded'])->assertForbidden();
        $this->actingAs($this->owner)->post("/admin/orders/{$o->id}/status", ['status' => 'refunded'])->assertRedirect();
        $this->assertSame(['refunded', 'refunded'], [$o->fresh()->status, $o->fresh()->payment_status]);
    }

    public function test_admin_pages_render(): void
    {
        $o = $this->order();
        Coupon::create(['code' => 'X1', 'type' => 'fixed', 'value' => 5]);
        $this->actingAs($this->owner);

        foreach (['/orders', '/orders?status=open', '/orders?status=pending', "/orders/{$o->id}", '/coupons', '/coupons/create', '/shipping', '/shipping/create', '/customers'] as $path) {
            $this->get('/admin'.$path)->assertOk();
        }
        $this->get('/admin/orders?status=bogus')->assertNotFound();
        $this->get("/admin/orders/{$o->id}")->assertSee($o->number)->assertSee('Update status');
        $this->get('/admin/customers')->assertSee('ali@example.com');
        $this->get('/admin')->assertOk()->assertSee('Revenue')->assertSee($o->number);
    }

    public function test_staff_can_view_orders_but_not_manage_sales_settings(): void
    {
        $o = $this->order();
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('staff')));

        $this->get('/admin/orders')->assertOk();
        $this->get('/admin/coupons')->assertForbidden();
        $this->get('/admin/shipping')->assertForbidden();
        $this->post("/admin/orders/{$o->id}/status", ['status' => 'confirmed'])->assertRedirect();   // staff may update orders
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('rider')))->get('/admin/customers')->assertForbidden();
    }

    public function test_coupon_admin_validation_and_crud(): void
    {
        $this->actingAs($this->owner);
        $this->post('/admin/coupons', ['code' => 'ab', 'type' => 'percent', 'value' => 10])->assertSessionHasErrors('code');
        $this->post('/admin/coupons', ['code' => 'BIG', 'type' => 'percent', 'value' => 150])->assertSessionHasErrors('value');
        $this->post('/admin/coupons', ['code' => 'BAD', 'type' => 'fixed'])->assertSessionHasErrors('value');
        $this->post('/admin/coupons', ['code' => 'RANGE', 'type' => 'fixed', 'value' => 5, 'starts_at' => '2026-12-10', 'ends_at' => '2026-12-01'])->assertSessionHasErrors('ends_at');

        $this->post('/admin/coupons', ['code' => ' eid20 ', 'type' => 'percent', 'value' => 20, 'max_discount' => 500, 'is_active' => 1])->assertRedirect('/admin/coupons');
        $c = Coupon::firstWhere('code', 'EID20');
        $this->assertEquals([20, 500], [$c->value, $c->max_discount]);
        $this->post('/admin/coupons', ['code' => 'EID20', 'type' => 'fixed', 'value' => 5])->assertSessionHasErrors('code');   // unique

        $this->put("/admin/coupons/{$c->id}", ['code' => 'EID20', 'type' => 'free_shipping'])->assertRedirect();
        $this->assertSame([0.0, null], [(float) $c->fresh()->value, $c->fresh()->max_discount]);
        $this->delete("/admin/coupons/{$c->id}")->assertRedirect();
        $this->assertNull(Coupon::find($c->id));
    }

    public function test_shipping_admin_crud(): void
    {
        $this->actingAs($this->owner);
        $this->post('/admin/shipping', ['name' => 'Lahore', 'cities' => 'Lahore, lahore, Gujranwala', 'is_active' => 1, 'rates' => [
            ['name' => 'Standard', 'price' => 250, 'free_over' => 4000, 'eta' => '2 days'],
            ['name' => 'Pickup', 'price' => 99, 'is_pickup' => 1],
        ]])->assertRedirect('/admin/shipping');

        $z = ShippingZone::firstWhere('name', 'Lahore')->load('rates');
        $this->assertSame(['Lahore', 'Gujranwala'], $z->cities);
        $this->assertCount(2, $z->rates);
        $this->assertTrue($z->rates[1]->is_pickup);
        $this->assertEquals(0, $z->rates[1]->price);   // pickup is forced free

        $this->post('/admin/shipping', ['name' => 'Empty', 'rates' => []])->assertSessionHasErrors('rates');

        $keep = $z->rates[0];
        $this->put("/admin/shipping/{$z->id}", ['name' => 'Lahore', 'cities' => '', 'rates' => [['id' => $keep->id, 'name' => 'Express', 'price' => 400]]])->assertRedirect();
        $z->refresh()->load('rates');
        $this->assertNull($z->cities);
        $this->assertSame(['Express'], $z->rates->pluck('name')->all());
        $this->delete("/admin/shipping/{$z->id}")->assertRedirect();
        $this->assertSoftDeleted('shipping_zones', ['id' => $z->id]);
    }

    public function test_payment_settings_validation_and_secret_handling(): void
    {
        $this->actingAs($this->owner);
        $base = [
            'store' => ['name' => 'X', 'currency' => 'PKR', 'currency_symbol' => 'Rs', 'timezone' => 'Asia/Karachi', 'default_locale' => 'en', 'locales' => ['en']],
            'theme' => ['primary' => '#112233', 'accent' => '#445566', 'mode' => 'dark'],
        ];

        $this->put('/admin/settings', $base + ['payments' => ['bank' => 1, 'bank_instructions' => '']])->assertSessionHasErrors('payments.bank_instructions');
        $this->put('/admin/settings', $base + ['payments' => ['stripe' => 1]])->assertSessionHasErrors('payments.stripe_secret');
        $this->put('/admin/settings', $base + ['payments' => ['stripe' => 1, 'stripe_secret' => 'not-a-key']])->assertSessionHasErrors('payments.stripe_secret');

        $this->put('/admin/settings', $base + ['payments' => ['cod' => 1, 'stripe' => 1, 'stripe_secret' => 'sk_test_abc123']])->assertRedirect();
        $this->assertTrue(setting('payments.stripe'));
        $this->assertSame('sk_test_abc123', (new StripeCheckout)->secret());
        $this->assertStringNotContainsString('sk_test_abc123', (string) setting('payments.stripe_secret'));

        // blank secret keeps the saved key
        $this->put('/admin/settings', $base + ['payments' => ['stripe' => 1]])->assertRedirect();
        $this->assertSame('sk_test_abc123', (new StripeCheckout)->secret());
        $this->get('/admin/settings')->assertOk()->assertDontSee('sk_test_abc123');
        $this->assertFalse(setting('payments.cod'));   // unchecked switch = off
    }

    public function test_dashboard_sales_numbers_ignore_cancelled_orders(): void
    {
        $a = $this->order();
        $b = $this->order();
        app(OrderService::class)->transition($b, 'cancelled', $this->owner);

        $this->actingAs($this->owner)->get('/admin')->assertOk()->assertSee('Rs 1,100.00');   // only the live order (2 × 500 + 100 shipping)
    }
}
