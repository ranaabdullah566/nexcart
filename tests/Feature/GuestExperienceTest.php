<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\Subscriber;
use App\Models\User;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
    }

    private function user(string $role): User
    {
        return tap(User::factory()->create(), fn ($u) => $u->assignRole($role));
    }

    private function msg(array $o = []): array
    {
        return $o + ['name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Do you deliver to Gulberg at night?'];
    }

    public function test_guest_can_send_a_message_and_staff_are_alerted(): void
    {
        $this->user('owner');
        setting()->setMany(['store.phone' => '021 111 222', 'store.address' => '12 Mall Road, Lahore', 'store.hours' => 'Mon–Sat 9–10']);
        $this->get('/contact')->assertOk()->assertSee('12 Mall Road, Lahore')->assertSee('Mon–Sat 9–10')->assertSee('021 111 222');

        $this->post('/contact', $this->msg(['subject' => 'Night delivery']))->assertRedirect('/contact')->assertSessionHas('contact_sent', 'Sara');
        $m = ContactMessage::firstOrFail();
        $this->assertSame(['Night delivery', 'new', null], [$m->subject, $m->status, $m->user_id]);
        $this->assertDatabaseHas('admin_alerts', ['message' => 'New message from Sara: Night delivery']);
        $this->get('/contact')->assertSee('Thank you');
    }

    public function test_contact_form_is_validated(): void
    {
        $this->post('/contact', $this->msg(['name' => '']))->assertSessionHasErrors('name');
        $this->post('/contact', $this->msg(['email' => 'nope']))->assertSessionHasErrors('email');
        $this->post('/contact', $this->msg(['message' => 'short']))->assertSessionHasErrors('message');
        $this->post('/contact', $this->msg(['phone' => 'abc']))->assertSessionHasErrors('phone');
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_bots_filling_the_hidden_field_are_blocked(): void
    {
        $this->withHeader('X-Enforce-Honeypot', '1')->post('/contact', $this->msg(['nx_website' => 'http://spam']))->assertSessionHas('error');
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_inbox_is_for_storefront_managers_and_handles_status_and_delete(): void
    {
        $this->post('/contact', $this->msg());
        $m = ContactMessage::firstOrFail();

        $this->actingAs($this->user('rider'));
        $this->get('/admin/messages')->assertForbidden();
        $this->put("/admin/messages/{$m->id}", ['status' => 'read'])->assertForbidden();

        $this->actingAs($this->user('manager'));
        $this->get('/admin/messages')->assertOk()->assertSee('Do you deliver to Gulberg');
        $this->put("/admin/messages/{$m->id}", ['status' => 'replied'])->assertRedirect();
        $this->assertSame('replied', $m->fresh()->status);
        $this->put("/admin/messages/{$m->id}", ['status' => 'bogus'])->assertSessionHasErrors('status');
        $this->delete("/admin/messages/{$m->id}")->assertRedirect();
        $this->assertSoftDeleted($m);
    }

    public function test_newsletter_signup_is_idempotent_and_unsubscribe_works(): void
    {
        $this->get('/')->assertSee('Get health tips');
        $this->post('/subscribe', ['email' => 'Fan@Example.com'])->assertRedirect()->assertSessionHas('notice');
        $this->post('/subscribe', ['email' => 'fan@example.com'])->assertRedirect()->assertSessionHas('notice');   // same message for a repeat
        $this->assertSame(1, Subscriber::count());
        $this->post('/subscribe', ['email' => 'bad'])->assertSessionHasErrors('email');

        $s = Subscriber::firstOrFail();
        $this->assertSame('fan@example.com', $s->email);
        $this->get('/unsubscribe/'.$s->token)->assertRedirect('/');
        $this->assertFalse($s->fresh()->is_active);
        $this->get('/unsubscribe/not-a-token')->assertRedirect('/');   // no error, nothing leaked

        $this->post('/subscribe', ['email' => 'fan@example.com']);   // signing up again re-subscribes
        $this->assertTrue($s->fresh()->is_active);

        setting()->set('sf.footer.show_newsletter', false);
        $this->get('/')->assertDontSee('Get health tips');
    }

    public function test_subscriber_export_lists_only_active_people_and_is_formula_safe(): void
    {
        Subscriber::signUp('a@example.com');
        Subscriber::signUp('gone@example.com')->update(['is_active' => false]);
        Subscriber::signUp('=cmd@example.com');

        $this->actingAs($this->user('rider'));
        $this->get('/admin/messages/subscribers.csv')->assertForbidden();

        $this->actingAs($this->user('owner'));
        $csv = $this->get('/admin/messages/subscribers.csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('a@example.com', $csv);
        $this->assertStringNotContainsString('gone@example.com', $csv);
        $this->assertStringContainsString("'=cmd@example.com", $csv);
    }

    private function placeGuestOrder(string $email = 'guest@example.com'): Order
    {
        $zone = ShippingZone::first() ?? ShippingZone::create(['name' => 'All', 'cities' => null]);
        $rate = $zone->rates()->first() ?? $zone->rates()->create(['name' => 'Standard', 'price' => 100]);
        $p = Product::first();
        if (! $p) {
            $p = new Product;
            $p->forceFill(['name' => 'Panadol', 'price' => 50, 'status' => 'active', 'track_stock' => false])->save();
        }
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 1])->assertOk();
        $this->post('/checkout', ['name' => 'Gul Khan', 'email' => $email, 'phone' => '0300 1112223', 'address_line' => 'House 5', 'city' => 'Lahore', 'shipping_rate_id' => $rate->id, 'payment_method' => 'cod'])->assertRedirect();

        return Order::firstOrFail();
    }

    public function test_guest_checkout_is_explained_and_offers_an_account_afterwards(): void
    {
        $p = new Product;
        $p->forceFill(['name' => 'Panadol', 'price' => 50, 'status' => 'active', 'track_stock' => false])->save();
        ShippingZone::create(['name' => 'All', 'cities' => null])->rates()->create(['name' => 'Standard', 'price' => 100]);
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 1]);
        $this->get('/checkout')->assertOk()->assertSee('No account needed');

        $order = $this->placeGuestOrder();   // reuses the product and rate above
        $this->assertNull($order->user_id);
        $this->get($order->trackUrl())->assertSee('Create a free account');
    }

    public function test_registering_after_a_guest_order_attaches_only_that_browsers_orders(): void
    {
        $mine = $this->placeGuestOrder('same@example.com');
        $stranger = Order::create(['number' => 'X-1', 'public_token' => str_repeat('b', 40), 'status' => 'pending', 'payment_status' => 'unpaid', 'payment_method' => 'cod', 'shipping_method' => 'S', 'name' => 'Other', 'email' => 'same@example.com', 'phone' => '1', 'subtotal' => 1, 'total' => 1, 'currency' => 'PKR', 'placed_at' => now()]);

        $this->post('/register', ['name' => 'Gul Khan', 'email' => 'same@example.com', 'password' => 'Sup3r-secret-pass', 'password_confirmation' => 'Sup3r-secret-pass'])->assertRedirect();
        $u = User::firstWhere('email', 'same@example.com');
        $this->assertSame($u->id, $mine->fresh()->user_id);
        $this->assertNull($stranger->fresh()->user_id);   // same e-mail address, but not placed from this browser
    }

    public function test_signing_in_after_a_guest_order_also_attaches_it(): void
    {
        $order = $this->placeGuestOrder();
        $u = User::factory()->create(['password' => 'Sup3r-secret-pass']);
        $this->post('/login', ['email' => $u->email, 'password' => 'Sup3r-secret-pass'])->assertRedirect();
        $this->assertSame($u->id, $order->fresh()->user_id);
    }

    public function test_register_form_is_prefilled_from_the_guest_order(): void
    {
        $this->placeGuestOrder('prefill@example.com');
        $this->get('/register')->assertSee('prefill@example.com')->assertSee('Gul Khan');
    }

    public function test_recently_viewed_install_button_and_offline_page_are_wired(): void
    {
        $p = new Product;
        $p->forceFill(['name' => "Children's Syrup", 'price' => 50, 'status' => 'active', 'track_stock' => false])->save();
        $html = $this->get('/product/'.$p->slug)->assertOk()->assertSee('data-fx-recent', false)->assertSee('Recently viewed')->getContent();
        preg_match('/data-fx-viewed="([^"]+)"/', $html, $m);
        $json = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
        $this->assertSame(["Children's Syrup", $p->id], [$json['name'], $json['id']]);

        $this->get('/')->assertSee('data-fx-recent', false)->assertSee('data-fx-install', false);
        $this->get('/cart')->assertSee('data-fx-recent', false);
        $this->assertStringContainsString('You are offline', file_get_contents(public_path('sw.js')));
    }

    public function test_accessibility_basics_are_in_the_markup(): void
    {
        $p = new Product;
        $p->forceFill(['name' => 'Panadol', 'price' => 50, 'status' => 'active', 'track_stock' => false, 'is_featured' => true])->save();
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('aria-label="Sign in"', $html);                // icon-only link on phones
        $this->assertStringContainsString('aria-label="Search products"', $html);        // unique landmark names
        $this->assertStringNotContainsString('<h6>', $html);                             // no skipped heading levels in the footer
        $this->assertDoesNotMatchRegularExpression('/<a[^>]*aria-hidden="true"[^>]*>(?:(?!<\/a>).)*<button/s', $html);   // no buttons inside hidden links

        $this->get('/blog')->assertOk()->assertSee('aria-label="Search articles…"', false);
        $this->assertStringContainsString('--acc-ink', $this->get('/')->getContent());   // readable accent for the light theme
    }

    public function test_marketplace_header_is_on_by_default_and_can_follow_the_theme(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(3, substr_count($html, 'mk-hd-light'));   // announcement strip, header row and navigation row
        $this->assertStringContainsString('<button type="submit" aria-label="Search"><span>Search</span></button>', $html);

        setting()->set('sf.header.light', false);
        setting()->set('sf.design.marketplace', false);
        $this->assertStringNotContainsString('mk-hd-light', $this->get('/')->getContent());
    }

    public function test_saved_medicines_and_compare_work_for_guests(): void
    {
        $p = new Product;
        $p->forceFill(['name' => 'Panadol', 'price' => 50, 'status' => 'active', 'track_stock' => false])->save();

        $this->get('/saved')->assertOk()->assertSee('tq-page-title', false);
        $this->get('/wishlist')->assertRedirect('/saved');
        $this->postJson('/saved/'.$p->id)->assertOk();
        $this->get('/saved')->assertOk()->assertSee('Panadol');
        $this->postJson('/compare/'.$p->id)->assertOk();
        $this->get('/compare')->assertOk()->assertSee('Panadol');
        $this->get('/')->assertOk()->assertSee('sf-tq', false);
    }
}
