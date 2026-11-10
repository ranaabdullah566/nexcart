<?php

namespace Tests\Feature;

use App\Mail\OrderPlaced;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\Payments\StripeCheckout;
use App\Services\PricingService;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private ShippingZone $khi;

    private ShippingRate $standard;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        setting()->setMany(['store.name' => 'Burger Hub', 'store.email' => 'shop@example.com', 'store.currency' => 'PKR', 'store.currency_symbol' => 'Rs']);
        Mail::fake();

        $this->khi = ShippingZone::create(['name' => 'Karachi', 'cities' => ['Karachi']]);
        $this->standard = $this->khi->rates()->create(['name' => 'Standard', 'price' => 200, 'free_over' => 5000, 'eta' => '2 days']);
        $rest = ShippingZone::create(['name' => 'Rest of Pakistan', 'cities' => null]);
        $rest->rates()->create(['name' => 'Courier', 'price' => 400]);
    }

    private function product(array $o = []): Product
    {
        $p = new Product;
        $p->forceFill($o + ['name' => 'Widget', 'price' => 1000, 'status' => 'active', 'track_stock' => true, 'stock_qty' => 10])->save();

        return $p;
    }

    private function cart(Product $p, int $qty = 1): void
    {
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => $qty])->assertOk();
    }

    private function form(array $o = []): array
    {
        return $o + [
            'name' => 'Ali Khan', 'email' => 'ali@example.com', 'phone' => '0300 1234567', 'address_line' => 'House 5, Street 2', 'city' => 'Karachi', 'area' => 'Clifton',
            'shipping_rate_id' => $this->standard->id, 'payment_method' => 'cod',
        ];
    }

    // ---------- pricing ----------

    public function test_pricing_with_coupons_tax_and_shipping(): void
    {
        $pricing = app(PricingService::class);
        $rate = $this->standard;

        $q = $pricing->quote(1000, null, $rate);
        $this->assertEquals([1000, 0, 200, 0, 1200], [$q['subtotal'], $q['discount'], $q['shipping'], $q['tax'], $q['total']]);

        $pct = Coupon::create(['code' => 'TEN', 'type' => 'percent', 'value' => 10, 'is_active' => true]);
        $this->assertEquals(1100, $pricing->quote(1000, $pct, $rate)['total']);   // 1000 - 100 + 200

        $capped = Coupon::create(['code' => 'CAP', 'type' => 'percent', 'value' => 50, 'max_discount' => 120, 'is_active' => true]);
        $this->assertEquals(120, $pricing->quote(1000, $capped, $rate)['discount']);

        $fixed = Coupon::create(['code' => 'FIVE', 'type' => 'fixed', 'value' => 5000, 'is_active' => true]);
        $this->assertEquals(1000, $pricing->quote(1000, $fixed, $rate)['discount']);   // never more than the goods
        $this->assertEquals(200, $pricing->quote(1000, $fixed, $rate)['total']);

        $ship = Coupon::create(['code' => 'SHIP', 'type' => 'free_shipping', 'is_active' => true]);
        $this->assertEquals(1000, $pricing->quote(1000, $ship, $rate)['total']);

        $this->assertEquals(0, $pricing->quote(5000, null, $rate)['shipping']);   // free over 5000

        setting()->setMany(['store.tax_rate' => 16, 'store.tax_inclusive' => false]);
        $q = $pricing->quote(1000, null, $rate);
        $this->assertEquals([160, 1360], [$q['tax'], $q['total']]);

        setting()->set('store.tax_inclusive', true);
        $q = $pricing->quote(1160, null, $rate);
        $this->assertEquals([160, 1360], [$q['tax'], $q['total']]);   // tax is inside the price; only shipping is added
    }

    public function test_coupon_rules(): void
    {
        $base = ['type' => 'fixed', 'value' => 10, 'is_active' => true];
        $this->assertNull(Coupon::create(['code' => 'OK'] + $base)->problem(100));
        $this->assertStringContainsString('not active', Coupon::create(['code' => 'OFF'] + array_merge($base, ['is_active' => false]))->problem(100));
        $this->assertStringContainsString('expired', Coupon::create(['code' => 'OLD', 'ends_at' => now()->subDay()] + $base)->problem(100));
        $this->assertStringContainsString('not valid yet', Coupon::create(['code' => 'SOON', 'starts_at' => now()->addDay()] + $base)->problem(100));
        $this->assertStringContainsString('Spend at least', Coupon::create(['code' => 'BIG', 'min_subtotal' => 500] + $base)->problem(100));
        $used = new Coupon(['code' => 'USED', 'usage_limit' => 1] + $base);
        $used->forceFill(['used_count' => 1])->save();
        $this->assertStringContainsString('fully used', $used->problem(100));
    }

    public function test_shipping_zone_matching_and_rate_rules(): void
    {
        $this->assertSame('Karachi', ShippingZone::forCity('karachi')->name);        // case-insensitive
        $this->assertSame('Rest of Pakistan', ShippingZone::forCity('Quetta')->name);  // catch-all
        $this->standard->update(['min_subtotal' => 300]);
        $this->assertFalse($this->standard->fresh()->isAvailableFor(100));
        $this->assertTrue($this->standard->fresh()->isAvailableFor(300));

        $pickup = $this->khi->rates()->create(['name' => 'Pickup', 'is_pickup' => true, 'price' => 99]);
        $this->assertSame(0.0, $pickup->costFor(100));   // pickup is always free
    }

    // ---------- checkout page & quote ----------

    public function test_checkout_needs_a_cart_then_shows_summary_and_methods(): void
    {
        $this->get('/checkout')->assertRedirect('/cart');

        $this->cart($this->product(['name' => 'Gadget']), 2);
        $this->get('/checkout')->assertOk()->assertSee('Gadget')->assertSee('Cash on delivery')->assertSee('Order summary')->assertSee('Complete order')->assertSee('shck-side', false)->assertSee('Contact')->assertSee('First name');
    }

    public function test_quote_endpoint_updates_methods_for_the_city_and_remembers_coupon(): void
    {
        $this->cart($this->product());
        Coupon::create(['code' => 'TEN', 'type' => 'percent', 'value' => 10, 'is_active' => true]);

        $r = $this->postJson('/checkout/quote', ['city' => 'Karachi', 'rate_id' => $this->standard->id, 'coupon' => 'ten'])->assertOk();
        $r->assertJsonPath('subtotal', 'Rs 1,000.00')->assertJsonPath('discount', '−Rs 100.00')->assertJsonPath('total', 'Rs 1,100.00')->assertJsonPath('coupon.code', 'TEN')->assertJsonPath('rates.0.name', 'Standard');
        $this->assertSame('TEN', session('coupon'));

        $this->postJson('/checkout/quote', ['city' => 'Quetta'])->assertJsonPath('rates.0.name', 'Courier');
        $this->postJson('/checkout/quote', ['city' => 'Karachi', 'coupon' => 'NOPE'])->assertJsonPath('coupon_error', 'We could not find that code.');
        $this->assertNull(session('coupon'));
    }

    // ---------- placing orders ----------

    public function test_guest_places_a_cash_order(): void
    {
        $p = $this->product(['price' => 1000, 'stock_qty' => 10]);
        $this->cart($p, 3);

        $res = $this->post('/checkout', $this->form(['notes' => 'Ring the bell']));
        $order = Order::firstOrFail();
        $res->assertRedirect($order->trackUrl())->assertSessionHas('placed');

        $this->assertSame(['pending', 'unpaid', 'cod', 'Standard'], [$order->status, $order->payment_status, $order->payment_method, $order->shipping_method]);
        $this->assertEquals([3000, 0, 200, 3200], [$order->subtotal, $order->discount, $order->shipping_cost, $order->total]);
        $this->assertSame('PKR', $order->currency);
        $this->assertMatchesRegularExpression('/^BUR-\d{6}$/', $order->number);
        $this->assertCount(1, $order->items);
        $this->assertSame([3, 'Widget'], [$order->items[0]->qty, $order->items[0]->name]);
        $this->assertSame(7, $p->fresh()->stock_qty);                                   // stock deducted
        $this->assertDatabaseHas('stock_movements', ['product_id' => $p->id, 'change' => -3, 'reason' => 'sold']);
        $this->assertDatabaseHas('order_events', ['order_id' => $order->id, 'type' => 'placed']);
        $this->getJson('/cart.json')->assertJsonPath('count', 0);                      // cart emptied
        Mail::assertSent(OrderPlaced::class, 2);                                        // customer + store

        $this->get($order->trackUrl())->assertOk()->assertSee($order->number)->assertSee('Rs 3,200.00')->assertSee('Cash on delivery');
        $this->get('/order/'.$order->number.'/wrongtoken')->assertNotFound();
    }

    public function test_order_snapshots_survive_product_edits_and_numbers_are_unique(): void
    {
        $p = $this->product(['name' => 'Old Name', 'price' => 500]);
        $this->cart($p);
        $this->post('/checkout', $this->form());
        $first = Order::firstOrFail();

        $p->update(['name' => 'New Name', 'price' => 999]);
        $this->assertSame(['Old Name', '500.00'], [$first->items()->first()->name, $first->items()->first()->unit_price]);

        $this->cart($p->fresh());
        $this->post('/checkout', $this->form());
        $this->assertNotSame($first->number, Order::latest('id')->first()->number);
    }

    public function test_coupon_is_applied_and_counted(): void
    {
        $this->cart($this->product());
        $c = Coupon::create(['code' => 'TEN', 'type' => 'percent', 'value' => 10, 'usage_limit' => 1, 'is_active' => true]);

        $this->post('/checkout', $this->form(['coupon' => 'ten']));
        $o = Order::firstOrFail();
        $this->assertEquals([100, 1100, 'TEN'], [$o->discount, $o->total, $o->coupon_code]);
        $this->assertSame(1, $c->fresh()->used_count);

        $this->cart($this->product());
        $this->post('/checkout', $this->form(['coupon' => 'TEN']))->assertSessionHasErrors('coupon');   // limit reached
        $this->assertSame(1, Order::count());
    }

    public function test_checkout_validation_and_guards(): void
    {
        $p = $this->product(['stock_qty' => 5]);
        $this->post('/checkout', $this->form())->assertSessionHasErrors('cart');   // empty cart

        $this->cart($p, 2);
        $this->post('/checkout', $this->form(['address_line' => '']))->assertSessionHasErrors('address_line');
        $this->post('/checkout', $this->form(['city' => '']))->assertSessionHasErrors('city');
        $this->post('/checkout', $this->form(['email' => 'nope']))->assertSessionHasErrors('email');
        $this->post('/checkout', $this->form(['phone' => 'abc']))->assertSessionHasErrors('phone');
        $this->post('/checkout', $this->form(['payment_method' => 'bitcoin']))->assertSessionHasErrors('payment_method');
        $this->post('/checkout', $this->form(['payment_method' => 'stripe']))->assertSessionHasErrors('payment_method');   // not enabled
        $this->post('/checkout', $this->form(['shipping_rate_id' => 9999]))->assertSessionHasErrors('shipping_rate_id');
        // a Karachi-only rate cannot be used for another city
        $this->post('/checkout', $this->form(['city' => 'Quetta']))->assertSessionHasErrors('shipping_rate_id');
        $this->assertSame(0, Order::count());
        $this->assertSame(5, $p->fresh()->stock_qty);   // nothing was deducted by the failed attempts
    }

    public function test_stock_is_rechecked_at_checkout(): void
    {
        $p = $this->product(['stock_qty' => 5]);
        $this->cart($p, 4);
        $p->update(['stock_qty' => 2]);   // someone else bought some

        $this->post('/checkout', $this->form())->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::count());
        $this->assertSame(2, $p->fresh()->stock_qty);
    }

    public function test_free_shipping_over_threshold(): void
    {
        $this->cart($this->product(['price' => 6000]));
        $this->post('/checkout', $this->form());
        $this->assertEquals([0, 6000], [Order::first()->shipping_cost, Order::first()->total]);
    }

    public function test_pickup_needs_no_address_and_is_a_module(): void
    {
        $pickup = $this->khi->rates()->create(['name' => 'Pick up from store', 'is_pickup' => true]);
        $this->cart($this->product());

        // module off: pickup is not offered
        setting()->set('modules.pickup', false);
        $this->assertNotContains($pickup->id, $this->postJson('/checkout/quote', ['city' => 'Karachi'])->json('rates.*.id'));
        $this->post('/checkout', $this->form(['shipping_rate_id' => $pickup->id]))->assertSessionHasErrors('shipping_rate_id');

        setting()->set('modules.pickup', true);
        $this->assertContains($pickup->id, $this->postJson('/checkout/quote', ['city' => 'Lahore'])->json('rates.*.id'));   // pickup ignores the city
        $this->post('/checkout', $this->form(['shipping_rate_id' => $pickup->id, 'address_line' => '', 'city' => '']))->assertRedirect();
        $o = Order::firstOrFail();
        $this->assertTrue($o->is_pickup);
        $this->assertNull($o->address_line);
        $this->assertSame(0.0, (float) $o->shipping_cost);
    }

    public function test_bank_transfer_shows_instructions(): void
    {
        setting()->setMany(['payments.bank' => true, 'payments.bank_instructions' => "HBL\nIBAN PK00TEST"]);
        $this->cart($this->product());
        $this->post('/checkout', $this->form(['payment_method' => 'bank']));

        $this->get(Order::first()->trackUrl())->assertSee('IBAN PK00TEST')->assertSee(Order::first()->number);
    }

    public function test_bank_transfer_hidden_without_instructions(): void
    {
        setting()->set('payments.bank', true);
        $this->cart($this->product());
        $this->get('/checkout')->assertDontSee('Bank transfer');
    }

    // ---------- Stripe ----------

    public function test_stripe_flow_redirects_then_verifies_payment(): void
    {
        StripeCheckout::storeSecret('sk_test_abc123');
        setting()->set('payments.stripe', true);
        $this->assertNotSame('sk_test_abc123', setting('payments.stripe_secret'));   // stored encrypted

        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_1'])]);
        $this->cart($this->product(['price' => 1000]));
        $this->post('/checkout', $this->form(['payment_method' => 'stripe']))->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_1');

        $o = Order::firstOrFail();
        $this->assertSame('cs_test_1', $o->payment_reference);
        Http::assertSent(fn ($r) => ($r->data()['line_items[0][price_data][unit_amount]'] ?? null) === 120000 && ($r->data()['client_reference_id'] ?? null) === $o->number && $r->hasHeader('Authorization'));

        $session = ['payment_status' => 'paid', 'client_reference_id' => $o->number, 'amount_total' => 120000];
        Http::fake(function ($r) use (&$session) {   // by reference: the test changes $session between requests
            return str_contains($r->url(), '/checkout/sessions/') ? Http::response($session) : Http::response([], 404);
        });
        $paid = fn (string $id) => "/order/{$o->number}/{$o->public_token}/paid?session_id={$id}";

        // a session id that is not the one stored on the order → still unpaid, Stripe is not even asked
        $this->get($paid('cs_evil'))->assertRedirect()->assertSessionHas('error');
        $this->assertSame('unpaid', $o->fresh()->payment_status);

        // right session but wrong amount or unpaid → unpaid
        $session['amount_total'] = 100;
        $this->get($paid('cs_test_1'))->assertSessionHas('error');
        $session['amount_total'] = 120000;
        $session['payment_status'] = 'unpaid';
        $this->get($paid('cs_test_1'))->assertSessionHas('error');
        $this->assertSame('unpaid', $o->fresh()->payment_status);

        $session['payment_status'] = 'paid';
        $this->get($paid('cs_test_1'))->assertSessionHas('success');
        $this->assertSame('paid', $o->fresh()->payment_status);
        $this->assertNotNull($o->fresh()->paid_at);
    }

    public function test_stripe_outage_still_saves_the_order(): void
    {
        StripeCheckout::storeSecret('sk_test_abc123');
        setting()->set('payments.stripe', true);
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['message' => 'down']], 500)]);

        $this->cart($this->product());
        $o = $this->post('/checkout', $this->form(['payment_method' => 'stripe']));
        $this->assertSame(1, Order::count());
        $o->assertRedirect(Order::first()->trackUrl())->assertSessionHas('error');
    }

    // ---------- prescriptions ----------

    public function test_prescription_is_required_stored_privately_and_viewable_by_staff_only(): void
    {
        Storage::fake('local');
        BusinessProfile::apply('pharmacy');
        $rx = $this->product(['name' => 'Antibiotic', 'requires_prescription' => true]);
        $this->cart($rx);

        $this->get('/checkout')->assertSee('Prescription');
        $this->post('/checkout', $this->form())->assertSessionHasErrors('prescription');
        $this->post('/checkout', $this->form(['prescription' => [UploadedFile::fake()->create('x.exe', 10, 'application/x-msdownload')]]))->assertSessionHasErrors('prescription.0');

        $this->post('/checkout', $this->form(['prescription' => [UploadedFile::fake()->image('rx.jpg')]]))->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertTrue($order->requires_prescription);
        $media = $order->getFirstMedia('prescriptions');
        $this->assertSame('local', $media->disk);

        $this->get("/admin/orders/{$order->id}/prescriptions/{$media->id}")->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get("/admin/orders/{$order->id}/prescriptions/{$media->id}")->assertForbidden();
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('pharmacist')))->get("/admin/orders/{$order->id}/prescriptions/{$media->id}")->assertOk();
    }

    // ---------- tracking & accounts ----------

    public function test_order_tracking_lookup_and_invoice(): void
    {
        $this->cart($this->product());
        $this->post('/checkout', $this->form());
        $o = Order::firstOrFail();

        $this->get('/track')->assertOk();
        $this->post('/track', ['number' => $o->number, 'email' => 'someone@else.com'])->assertSessionHasErrors('number');
        $this->post('/track', ['number' => strtolower($o->number), 'email' => 'ALI@example.com'])->assertRedirect($o->trackUrl());
        $this->get($o->trackUrl().'/invoice')->assertOk()->assertSee('Print')->assertSee($o->number)->assertSee('Rs 1,200.00');
    }

    public function test_customer_accounts_prefill_checkout_and_list_orders(): void
    {
        $this->post('/register', ['name' => 'Sara', 'email' => 'sara@example.com', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])->assertRedirect('/account');
        $sara = User::firstWhere('email', 'sara@example.com');
        $this->assertFalse($sara->isStaff());
        $this->get('/admin')->assertForbidden();

        $this->cart($this->product());
        $this->post('/checkout', $this->form(['name' => 'Sara', 'email' => 'sara@example.com']));
        $o = Order::firstOrFail();
        $this->assertSame($sara->id, $o->user_id);
        $this->assertSame('Karachi', $sara->fresh()->default_address['city']);   // remembered for next time

        $this->get('/account')->assertOk()->assertSee($o->number);
        $this->cart($this->product());
        $this->get('/checkout')->assertSee('House 5, Street 2')->assertSee('0300 1234567');

        $this->assertSame(1, $sara->addresses()->count());   // the first order fills the address book
        $this->put('/account', ['name' => 'Sara K', 'email' => 'sara@example.com'])->assertSessionHas('success');
        $this->assertSame('Sara K', $sara->fresh()->name);
    }

    public function test_login_sends_customers_to_account_and_staff_to_admin(): void
    {
        User::factory()->create(['email' => 'c@example.com', 'password' => 'secret-pass-1']);
        $this->post('/login', ['email' => 'c@example.com', 'password' => 'secret-pass-1'])->assertRedirect('/account');
        $this->post('/logout');

        $staff = tap(User::factory()->create(['email' => 's@example.com', 'password' => 'secret-pass-1']), fn ($u) => $u->assignRole('manager'));
        $this->post('/login', ['email' => 's@example.com', 'password' => 'secret-pass-1'])->assertRedirect('/admin');
    }

    public function test_customers_cannot_see_each_others_orders_without_the_token(): void
    {
        $this->cart($this->product());
        $this->post('/checkout', $this->form());
        $o = Order::firstOrFail();

        $this->get("/order/{$o->number}/".str_repeat('a', 40))->assertNotFound();
        $this->get("/order/{$o->number}/short")->assertNotFound();
    }

    public function test_emails_render_in_all_languages(): void
    {
        $this->cart($this->product());
        $this->post('/checkout', $this->form());
        $o = Order::firstOrFail();

        foreach (['en', 'ur', 'ur_Latn'] as $loc) {
            app()->setLocale($loc);
            $html = (new OrderPlaced($o))->render();
            $this->assertStringContainsString($o->number, $html);
        }
    }

    public function test_shopify_style_form_joins_first_and_last_name_and_signs_up_for_news(): void
    {
        $this->cart($this->product());
        $form = $this->form(['name' => '', 'email' => 'news@example.com']);
        unset($form['name']);
        $this->post('/checkout', $form + ['first_name' => 'Sara', 'last_name' => 'Khan', 'news' => 1, 'country' => 'Pakistan'])->assertSessionHasNoErrors();
        $this->assertSame('Sara Khan', Order::firstOrFail()->name);
        $this->assertDatabaseHas('subscribers', ['email' => 'news@example.com', 'is_active' => true]);

        $this->cart($this->product());
        $this->post('/checkout', $this->form(['email' => 'nonews@example.com']))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('subscribers', ['email' => 'nonews@example.com']);
        $this->cart($this->product());
        $this->post('/checkout', $this->form(['first_name' => '<script>x</script>']))->assertSessionHasErrors('first_name');
    }
}
