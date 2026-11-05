<?php

namespace Tests\Feature;

use App\Mail\PrescriptionReviewed;
use App\Models\DeliverySlot;
use App\Models\ModifierGroup;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\OrderService;
use App\Services\SlotService;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BusinessModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        setting()->set('store.timezone', 'Asia/Karachi');
        Carbon::setTestNow(Carbon::parse('2026-11-10 10:00:00', 'Asia/Karachi'));
        Mail::fake();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $role): User
    {
        return tap(User::factory()->create(), fn ($u) => $u->assignRole($role));
    }

    private function zone(array $rate = []): ShippingZone
    {
        $zone = ShippingZone::create(['name' => 'All', 'cities' => null]);
        $zone->rates()->create($rate + ['name' => 'Delivery', 'price' => 100, 'eta_minutes' => 30]);
        $zone->rates()->create(['name' => 'Pickup', 'is_pickup' => true, 'eta_minutes' => 15]);

        return $zone;
    }

    private function product(array $o = []): Product
    {
        $p = new Product;
        $p->forceFill($o + ['name' => 'Item', 'price' => 500, 'status' => 'active', 'track_stock' => true, 'stock_qty' => 20])->save();

        return $p;
    }

    private function form(array $o = []): array
    {
        $rate = ShippingZone::first()->rates()->where('is_pickup', false)->first();

        return $o + ['name' => 'Ali', 'email' => 'ali@example.com', 'phone' => '0300111222', 'address_line' => 'Somewhere 1', 'city' => 'Karachi', 'shipping_rate_id' => $rate->id, 'payment_method' => 'cod'];
    }

    private function place(array $form = [], array $cart = []): Order
    {
        $p = $cart['product'] ?? $this->product();
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => $cart['qty'] ?? 1] + array_diff_key($cart, ['product' => 1, 'qty' => 1]))->assertOk();
        $this->post('/checkout', $this->form($form));

        return Order::latest('id')->firstOrFail();
    }

    // ================= delivery slots =================

    public function test_slots_are_listed_with_open_and_closed_windows(): void
    {
        BusinessProfile::apply('pharmacy');
        $this->zone();
        foreach ([['Morning', '09:00', '12:00'], ['Afternoon', '12:00', '16:00']] as [$l, $a, $b]) {
            DeliverySlot::create(['label' => $l, 'starts_at' => $a, 'ends_at' => $b, 'is_active' => true]);
        }

        $days = app(SlotService::class)->days();
        $this->assertCount(4, $days);                                  // today + 3 days
        $this->assertSame('Today', $days[0]['label']);
        $this->assertFalse($days[0]['slots'][0]['open']);              // 09:00 slot already started
        $this->assertTrue($days[0]['slots'][1]['open']);               // 12:00 slot closes at 11:00 (60 min cut-off) — it is 10:00
        $this->assertTrue($days[1]['slots'][0]['open']);

        Carbon::setTestNow(Carbon::parse('2026-11-10 11:30:00', 'Asia/Karachi'));
        $this->assertFalse(app(SlotService::class)->days()[0]['slots'][1]['open']);
    }

    public function test_checkout_requires_a_valid_slot_and_stores_it(): void
    {
        BusinessProfile::apply('pharmacy');
        $this->zone();
        $am = DeliverySlot::create(['label' => 'Morning', 'starts_at' => '09:00', 'ends_at' => '12:00', 'is_active' => true]);
        $pm = DeliverySlot::create(['label' => 'Evening', 'starts_at' => '16:00', 'ends_at' => '20:00', 'is_active' => true]);

        $p = $this->product();
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 1]);
        $this->get('/checkout')->assertOk()->assertSee('Delivery time')->assertSee('Evening · 16:00–20:00');

        $this->post('/checkout', $this->form())->assertSessionHasErrors('delivery_slot_id');                                           // none chosen
        $this->post('/checkout', $this->form(['slot_date' => '2026-11-10', 'delivery_slot_id' => $am->id]))->assertSessionHasErrors('delivery_slot_id'); // started already
        $this->post('/checkout', $this->form(['slot_date' => '2026-11-09', 'delivery_slot_id' => $pm->id]))->assertSessionHasErrors('delivery_slot_id'); // yesterday
        $this->post('/checkout', $this->form(['slot_date' => '2026-11-20', 'delivery_slot_id' => $pm->id]))->assertSessionHasErrors('delivery_slot_id'); // too far ahead
        $this->assertSame(0, Order::count());

        $this->post('/checkout', $this->form(['slot_date' => '2026-11-10', 'delivery_slot_id' => $pm->id]))->assertRedirect();
        $o = Order::firstOrFail();
        $this->assertSame(['2026-11-10', $pm->id, 'Evening · 16:00–20:00'], [$o->slot_date->toDateString(), $o->delivery_slot_id, $o->slot_label]);
        $this->get($o->trackUrl())->assertSee('Evening · 16:00–20:00');
    }

    public function test_slot_capacity_is_enforced_and_cancelling_frees_a_place(): void
    {
        BusinessProfile::apply('pharmacy');
        $this->zone();
        $slot = DeliverySlot::create(['label' => 'Evening', 'starts_at' => '16:00', 'ends_at' => '20:00', 'capacity' => 1, 'is_active' => true]);
        $f = ['slot_date' => '2026-11-11', 'delivery_slot_id' => $slot->id];

        $first = $this->place($f);
        $this->assertSame($slot->id, $first->delivery_slot_id);

        $p = $this->product();
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 1]);
        $this->post('/checkout', $this->form($f))->assertSessionHasErrors('delivery_slot_id');   // full
        $this->assertSame(1, Order::count());

        app(OrderService::class)->transition($first, 'cancelled');
        $this->post('/checkout', $this->form($f))->assertRedirect();                              // place is free again
        $this->assertSame(2, Order::count());
    }

    public function test_slots_are_off_without_the_module_or_without_slots(): void
    {
        BusinessProfile::apply('general');
        $this->zone();
        DeliverySlot::create(['label' => 'X', 'starts_at' => '09:00', 'ends_at' => '10:00', 'is_active' => true]);
        $this->assertFalse(app(SlotService::class)->enabled());
        $this->assertSame('pending', $this->place()->status);   // no slot needed

        BusinessProfile::apply('pharmacy');
        DeliverySlot::query()->delete();
        $this->assertFalse(app(SlotService::class)->enabled());
    }

    public function test_slot_admin_crud_settings_and_gating(): void
    {
        BusinessProfile::apply('pharmacy');
        $this->actingAs($this->user('owner'));

        $this->get('/admin/delivery-slots')->assertOk();
        $this->post('/admin/delivery-slots', ['label' => 'Night', 'starts_at' => '20:00', 'ends_at' => '19:00'])->assertSessionHasErrors('ends_at');
        $this->post('/admin/delivery-slots', ['label' => 'Night', 'starts_at' => '25:00', 'ends_at' => '26:00'])->assertSessionHasErrors('starts_at');
        $this->post('/admin/delivery-slots', ['label' => 'Night', 'starts_at' => '18:00', 'ends_at' => '21:00', 'capacity' => 5, 'is_active' => 1])->assertRedirect('/admin/delivery-slots');
        $s = DeliverySlot::firstWhere('label', 'Night');
        $this->assertSame(5, $s->capacity);

        $this->put("/admin/delivery-slots/{$s->id}", ['label' => 'Night', 'starts_at' => '18:00', 'ends_at' => '22:00'])->assertRedirect();
        $this->assertSame([null, false, '22:00'], [$s->fresh()->capacity, $s->fresh()->is_active, $s->fresh()->ends_at]);

        $this->post('/admin/delivery-slots/settings', ['days_ahead' => 20, 'cutoff_minutes' => 30])->assertSessionHasErrors('days_ahead');
        $this->post('/admin/delivery-slots/settings', ['days_ahead' => 5, 'cutoff_minutes' => 30])->assertRedirect();
        $this->assertSame([5, 30], [(int) setting('delivery.days_ahead'), (int) setting('delivery.cutoff_minutes')]);

        $this->delete("/admin/delivery-slots/{$s->id}")->assertRedirect();
        $this->assertNull(DeliverySlot::find($s->id));

        BusinessProfile::apply('general');
        setting()->set('modules.delivery_slots', false);
        $this->get('/admin/delivery-slots')->assertNotFound();
        $this->actingAs($this->user('staff'))->get('/admin/delivery-slots')->assertNotFound();
    }

    // ================= prescriptions =================

    private function rxOrder(): Order
    {
        BusinessProfile::apply('pharmacy');
        DeliverySlot::query()->delete();   // keep the focus on prescriptions
        $this->zone();
        $rx = $this->product(['name' => 'Antibiotic', 'requires_prescription' => true]);
        $this->postJson('/cart/add', ['product_id' => $rx->id, 'qty' => 1]);
        $this->post('/checkout', $this->form(['prescription' => [UploadedFile::fake()->image('rx.jpg')]]))->assertRedirect();

        return Order::firstOrFail();
    }

    public function test_orders_with_prescriptions_wait_for_approval_before_preparing(): void
    {
        $o = $this->rxOrder();
        $svc = app(OrderService::class);
        $pharmacist = $this->user('pharmacist');

        $this->assertSame('pending', $o->prescription_status);
        $this->assertTrue($o->awaitsPrescription());
        $svc->transition($o, 'confirmed');                       // confirming is fine…

        try {
            $svc->transition($o->fresh(), 'preparing');          // …preparing is not
            $this->fail('should have been blocked');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('prescription', strtolower($e->errors()['status'][0]));
        }

        $svc->reviewPrescription($o->fresh(), 'approved', null, $pharmacist);
        $svc->transition($o->fresh(), 'preparing');
        $this->assertSame('preparing', $o->fresh()->status);
        $this->assertSame($pharmacist->id, $o->fresh()->prescription_reviewed_by);
    }

    public function test_pharmacist_reviews_from_the_queue_and_customer_is_emailed(): void
    {
        $o = $this->rxOrder();
        $this->actingAs($this->user('pharmacist'));

        $this->get('/admin/prescriptions')->assertOk()->assertSee($o->number);
        $this->get("/admin/prescriptions/{$o->id}")->assertOk()->assertSee('Antibiotic')->assertSee('Approve');

        $this->post("/admin/prescriptions/{$o->id}/review", ['decision' => 'rejected'])->assertSessionHasErrors('note');   // reason required
        $this->post("/admin/prescriptions/{$o->id}/review", ['decision' => 'rejected', 'note' => 'Photo is blurry'])->assertRedirect('/admin/prescriptions');
        $this->assertSame(['rejected', 'Photo is blurry'], [$o->fresh()->prescription_status, $o->fresh()->prescription_note]);
        Mail::assertSent(PrescriptionReviewed::class, fn ($m) => $m->hasTo('ali@example.com') && $m->order->prescription_status === 'rejected');

        $this->post("/admin/prescriptions/{$o->id}/review", ['decision' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $o->fresh()->prescription_status);
        $this->assertDatabaseHas('order_events', ['order_id' => $o->id, 'type' => 'prescription', 'status' => 'approved']);
        $this->get('/admin/prescriptions?status=approved')->assertSee($o->number);
        $this->get('/admin/prescriptions?status=nope')->assertNotFound();
    }

    public function test_customer_can_replace_a_rejected_prescription(): void
    {
        $o = $this->rxOrder();
        $svc = app(OrderService::class);
        $this->post("/order/{$o->number}/{$o->public_token}/prescription", ['prescription' => [UploadedFile::fake()->image('x.jpg')]])->assertNotFound();   // only after a rejection

        $svc->reviewPrescription($o, 'rejected', 'Too dark', $this->user('pharmacist'));
        $this->get($o->trackUrl())->assertSee('Too dark')->assertSee('Upload a new prescription');

        $this->post("/order/{$o->number}/{$o->public_token}/prescription", ['prescription' => [UploadedFile::fake()->create('x.exe', 5, 'application/x-msdownload')]])->assertSessionHasErrors('prescription.0');
        $this->post("/order/{$o->number}/{$o->public_token}/prescription", ['prescription' => [UploadedFile::fake()->image('new.jpg')]])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(['pending', null], [$o->fresh()->prescription_status, $o->fresh()->prescription_note]);
        $this->assertCount(2, $o->fresh()->getMedia('prescriptions'));
    }

    public function test_prescription_permissions_and_module_gate(): void
    {
        $o = $this->rxOrder();

        $this->actingAs($this->user('rider'))->get('/admin/prescriptions')->assertForbidden();
        $this->actingAs($this->user('staff'))->post("/admin/prescriptions/{$o->id}/review", ['decision' => 'approved'])->assertForbidden();   // staff cannot verify

        $this->actingAs($this->user('owner'));
        setting()->set('modules.prescriptions', false);
        $this->get('/admin/prescriptions')->assertNotFound();
    }

    // ================= riders =================

    private function deliveryOrder(): Order
    {
        BusinessProfile::apply('fastfood');
        $this->zone();

        return $this->place();
    }

    public function test_delivery_flow_goes_through_ready_when_riders_are_on(): void
    {
        $o = $this->deliveryOrder();
        $svc = app(OrderService::class);

        foreach (['confirmed', 'preparing'] as $s) {
            $svc->transition($o->fresh(), $s);
        }
        $this->assertSame(['ready', 'cancelled'], $o->fresh()->nextStatuses());
        $svc->transition($o->fresh(), 'ready');
        $this->assertSame(['out_for_delivery', 'cancelled'], $o->fresh()->nextStatuses());
        $this->assertSame(2, $o->fresh()->timelineIndex());   // "ready" still shows as preparing to the customer

        setting()->set('modules.riders', false);
        $o2 = $this->place();
        foreach (['confirmed', 'preparing'] as $s) {
            $svc->transition($o2->fresh(), $s);
        }
        $this->assertSame(['out_for_delivery', 'cancelled'], $o2->fresh()->nextStatuses());   // without riders the kitchen dispatches directly
    }

    public function test_assigning_riders_and_rider_console(): void
    {
        $o = $this->deliveryOrder();
        $mine = $this->user('rider');
        $other = $this->user('rider');
        $manager = $this->user('manager');
        $svc = app(OrderService::class);

        $this->actingAs($manager)->post("/admin/orders/{$o->id}/rider", ['rider_id' => $manager->id])->assertSessionHasErrors('rider_id');   // not a rider
        $this->post("/admin/orders/{$o->id}/rider", ['rider_id' => $mine->id])->assertRedirect();
        $this->assertSame($mine->id, $o->fresh()->rider_id);
        $this->get("/admin/orders/{$o->id}")->assertSee($mine->name);

        foreach (['confirmed', 'preparing', 'ready'] as $s) {
            $svc->transition($o->fresh(), $s);
        }

        // the other rider sees nothing and cannot move my order
        $this->actingAs($other)->get('/admin/deliveries')->assertOk()->assertDontSee($o->number);
        $this->post("/admin/deliveries/{$o->id}/advance")->assertForbidden();

        $this->actingAs($mine)->get('/admin/deliveries')->assertOk()->assertSee($o->number)->assertSee('Collect cash')->assertSee('maps/search');
        $this->post("/admin/deliveries/{$o->id}/advance")->assertRedirect();
        $this->assertSame('out_for_delivery', $o->fresh()->status);
        $this->post("/admin/deliveries/{$o->id}/advance")->assertRedirect();
        $o->refresh();
        $this->assertSame(['completed', 'paid'], [$o->status, $o->payment_status]);   // cash collected at the door
        $this->post("/admin/deliveries/{$o->id}/advance")->assertStatus(422);        // nothing left to do

        $this->actingAs($manager)->get('/admin/deliveries')->assertOk();
        $this->post("/admin/orders/{$o->id}/rider", ['rider_id' => $mine->id])->assertSessionHasErrors('rider_id');   // completed orders cannot be reassigned
    }

    public function test_pickup_orders_cannot_get_a_rider(): void
    {
        BusinessProfile::apply('fastfood');
        $zone = $this->zone();
        $pickup = $zone->rates()->where('is_pickup', true)->first();
        $o = $this->place(['shipping_rate_id' => $pickup->id, 'address_line' => null, 'city' => null]);

        $this->actingAs($this->user('manager'))->post("/admin/orders/{$o->id}/rider", ['rider_id' => $this->user('rider')->id])->assertSessionHasErrors('rider_id');
    }

    public function test_riders_module_off_hides_the_console(): void
    {
        BusinessProfile::apply('general');
        $this->actingAs($this->user('rider'))->get('/admin/deliveries')->assertNotFound();
    }

    // ================= kitchen & ETA =================

    public function test_kitchen_board_moves_orders_along_and_never_dispatches_delivery(): void
    {
        $o = $this->deliveryOrder();
        $this->actingAs($this->user('staff'));

        $this->get('/admin/kitchen')->assertOk()->assertSee($o->number)->assertSee('Accept');
        $this->get('/admin/kitchen/feed')->assertOk()->assertSee('data-new="1"', false);

        $this->post("/admin/kitchen/{$o->id}/advance");
        $this->assertSame('confirmed', $o->fresh()->status);
        $this->get('/admin/kitchen/feed')->assertSee('Start cooking')->assertSee('data-new="0"', false);
        $this->post("/admin/kitchen/{$o->id}/advance");
        $this->post("/admin/kitchen/{$o->id}/advance");
        $this->assertSame('ready', $o->fresh()->status);

        $this->get('/admin/kitchen/feed')->assertSee('Waiting for a rider');
        $this->post("/admin/kitchen/{$o->id}/advance")->assertStatus(422);   // handing a delivery to the customer is the rider's job
        $this->assertSame('ready', $o->fresh()->status);
    }

    public function test_kitchen_hands_over_pickup_orders_and_respects_prescriptions(): void
    {
        BusinessProfile::apply('fastfood');
        $zone = $this->zone();
        $pickup = $zone->rates()->where('is_pickup', true)->first();
        $o = $this->place(['shipping_rate_id' => $pickup->id, 'address_line' => null, 'city' => null]);
        $this->actingAs($this->user('staff'));

        foreach (range(1, 4) as $_) {
            $this->post("/admin/kitchen/{$o->id}/advance");
        }
        $this->assertSame('completed', $o->fresh()->status);
        $this->assertSame('paid', $o->fresh()->payment_status);   // paid in cash at the counter

        // an order waiting for a prescription is shown but cannot be advanced
        $o2 = $this->place(['shipping_rate_id' => $pickup->id, 'address_line' => null, 'city' => null]);
        $o2->forceFill(['requires_prescription' => true, 'prescription_status' => 'pending'])->save();
        $this->post("/admin/kitchen/{$o2->id}/advance");   // pending → confirmed is allowed
        $this->post("/admin/kitchen/{$o2->id}/advance")->assertSessionHasErrors('status');
        $this->assertSame('confirmed', $o2->fresh()->status);
    }

    public function test_kitchen_is_a_module_and_needs_permission(): void
    {
        BusinessProfile::apply('general');
        $this->actingAs($this->user('staff'))->get('/admin/kitchen')->assertNotFound();

        BusinessProfile::apply('fastfood');
        $this->actingAs($this->user('rider'))->get('/admin/kitchen')->assertOk();   // riders may view orders
        $this->actingAs(User::factory()->create())->get('/admin/kitchen')->assertForbidden();
    }

    public function test_eta_uses_travel_time_plus_slowest_item_prep(): void
    {
        BusinessProfile::apply('fastfood');
        $this->zone();
        $slow = $this->product(['name' => 'Slow Pizza', 'specs' => ['prep_time_min' => 20]]);
        $fast = $this->product(['name' => 'Cola', 'specs' => ['prep_time_min' => 2]]);
        $this->postJson('/cart/add', ['product_id' => $fast->id, 'qty' => 1]);
        $this->postJson('/cart/add', ['product_id' => $slow->id, 'qty' => 1]);
        $this->post('/checkout', $this->form())->assertRedirect();

        $o = Order::firstOrFail();
        $this->assertTrue($o->eta_at->equalTo(now()->addMinutes(50)));   // 20 prep + 30 travel
        $this->get($o->trackUrl())->assertSee('Estimated arrival')->assertSee('10:50 AM');

        $pickup = ShippingZone::first()->rates()->where('is_pickup', true)->first();
        $this->postJson('/cart/add', ['product_id' => $fast->id, 'qty' => 1]);
        $this->post('/checkout', $this->form(['shipping_rate_id' => $pickup->id, 'address_line' => null]));
        $this->assertTrue(Order::latest('id')->first()->eta_at->equalTo(now()->addMinutes(17)));   // 2 + 15
    }

    // ================= reorder & starter data =================

    public function test_reorder_rebuilds_the_cart_with_current_prices_and_skips_unavailable_items(): void
    {
        BusinessProfile::apply('fastfood');
        $this->zone();
        $group = ModifierGroup::create(['name' => 'Extras', 'min_select' => 1, 'max_select' => 1]);
        $cheese = $group->options()->create(['name' => 'Cheese', 'price_delta' => 80]);
        $burger = $this->product(['name' => 'Burger', 'price' => 500]);
        $burger->modifierGroups()->attach($group);
        $gone = $this->product(['name' => 'Seasonal']);

        $this->postJson('/cart/add', ['product_id' => $burger->id, 'qty' => 2, 'options' => [$cheese->id]]);
        $this->postJson('/cart/add', ['product_id' => $gone->id, 'qty' => 1]);
        $this->post('/checkout', $this->form());
        $o = Order::firstOrFail();
        $this->assertSame($cheese->id, $o->items[0]->options[0]['id']);

        $burger->update(['price' => 600]);
        $gone->update(['status' => 'archived']);

        $this->post("/order/{$o->number}/{$o->public_token}/reorder")->assertRedirect('/cart')->assertSessionHas('error');
        $this->get('/cart')->assertSee('No longer available: Seasonal')->assertSee('items added to your cart');
        $cart = $this->getJson('/cart.json')->assertJsonPath('count', 2)->json();
        $this->assertSame('Rs 1,360.00', $cart['subtotal']);   // 2 × (600 + 80) at today's price, extras kept
        $this->post("/order/{$o->number}/wrong/reorder")->assertNotFound();
    }

    public function test_setup_creates_starter_shipping_and_slots(): void
    {
        setting()->set('system.installed', false);
        $this->post('/setup', [
            'business_type' => 'pharmacy', 'store_name' => 'Health Plus', 'currency' => 'PKR', 'currency_symbol' => 'Rs', 'timezone' => 'Asia/Karachi',
            'name' => 'Owner', 'email' => 'o@example.com', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect();

        $this->assertSame(1, ShippingZone::count());
        $this->assertTrue(ShippingZone::first()->rates()->where('is_pickup', false)->exists());
        $this->assertFalse(ShippingZone::first()->rates()->where('is_pickup', true)->exists());   // pharmacy profile has no pickup
        $this->assertSame(['Morning', 'Afternoon', 'Evening'], DeliverySlot::orderBy('sort_order')->pluck('label')->all());
    }
}
