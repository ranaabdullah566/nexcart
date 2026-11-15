<?php

namespace Tests\Feature;

use App\Mail\AbandonedCartReminder;
use App\Mail\OrderPlaced;
use App\Mail\OrderStatusChanged;
use App\Models\AbandonedCheckout;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Review;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GrowthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        setting()->set('modules.riders', false);   // direct dispatch flow, no rider hand-off
        setting()->setMany(['store.name' => 'Shop', 'store.currency_symbol' => 'Rs', 'store.timezone' => 'Asia/Karachi']);
        Mail::fake();
        Storage::fake('public');
        $zone = ShippingZone::create(['name' => 'All', 'cities' => null]);
        $zone->rates()->create(['name' => 'Standard', 'price' => 100]);
    }

    private function user(string $role): User
    {
        return tap(User::factory()->create(), fn ($u) => $u->assignRole($role));
    }

    private function owner(): User
    {
        return $this->user('owner');
    }

    private function product(array $o = []): Product
    {
        $p = new Product;
        $p->forceFill($o + ['name' => 'Widget', 'price' => 1000, 'status' => 'active', 'track_stock' => true, 'stock_qty' => 50])->save();

        return $p;
    }

    /** Place an order for the cart contents and (optionally) walk it to a status. */
    private function order(?User $user = null, array $extra = [], array $cart = [], ?string $status = null): Order
    {
        $p = $cart['product'] ?? $this->product();
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => $cart['qty'] ?? 1])->assertOk();
        $order = app(CheckoutService::class)->place($extra + [
            'name' => 'Ali Khan', 'email' => 'ali@example.com', 'phone' => '0300 1234567', 'address_line' => 'House 1', 'city' => 'Karachi',
            'shipping_rate_id' => ShippingZone::first()->rates()->first()->id, 'payment_method' => 'cod',
        ], $user);

        foreach (['completed' => ['confirmed', 'preparing', 'out_for_delivery', 'completed'], 'confirmed' => ['confirmed']][$status] ?? [] as $s) {
            app(OrderService::class)->transition($order->fresh(), $s);
        }

        return $order->fresh();
    }

    // ================= theme editor =================

    public function test_theme_is_saved_and_applied_to_the_storefront(): void
    {
        $this->actingAs($this->owner());
        $this->get('/admin/theme')->assertOk()->assertSee('Live preview')->assertSee('id="preview"', false);

        $this->put('/admin/theme', ['primary' => '#112233', 'accent' => '#aabbcc', 'mode' => 'light', 'radius' => '0.5rem', 'font' => 'inter',
            'announce_enabled' => 1, 'announce_text' => 'Free delivery today', 'announce_url' => '/shop', 'tr' => ['ur' => ['announce_text' => 'آج مفت ڈیلیوری']]])->assertRedirect('/admin/theme');

        auth()->logout();
        $this->get('/')->assertSee('--acc-1: #aabbcc', false)->assertSee('--radius: 0.5rem', false)->assertSee("--font-display: 'Inter'", false)->assertSee('Free delivery today')->assertSee('href="/shop"', false);
        $this->withSession(['locale' => 'ur'])->get('/')->assertSee('آج مفت ڈیلیوری')->assertDontSee('Free delivery today');
    }

    public function test_theme_validation_uploads_and_permissions(): void
    {
        $this->actingAs($this->owner());
        $base = ['primary' => '#112233', 'accent' => '#aabbcc', 'mode' => 'dark', 'radius' => '1rem', 'font' => 'space'];

        $this->put('/admin/theme', ['primary' => 'red'] + $base)->assertSessionHasErrors('primary');
        $this->put('/admin/theme', ['radius' => '99px'] + $base)->assertSessionHasErrors('radius');
        $this->put('/admin/theme', ['font' => 'comic'] + $base)->assertSessionHasErrors('font');
        $this->put('/admin/theme', ['announce_url' => 'javascript:alert(1)'] + $base)->assertSessionHasErrors('announce_url');
        $this->put('/admin/theme', ['logo' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml')] + $base)->assertSessionHasErrors('logo');   // SVG can carry scripts
        $this->put('/admin/theme', ['logo' => UploadedFile::fake()->create('x.exe', 5, 'application/x-msdownload')] + $base)->assertSessionHasErrors('logo');

        $this->put('/admin/theme', ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)] + $base)->assertRedirect();
        $path = setting('brand.logo');
        Storage::disk('public')->assertExists($path);
        $this->assertSame(asset('storage/'.$path), brand_logo());
        $this->get('/')->assertSee(brand_logo());

        $this->put('/admin/theme', ['remove_logo' => 1] + $base)->assertRedirect();
        Storage::disk('public')->assertMissing($path);
        $this->assertStringContainsString('assets/images/logo.svg', brand_logo());

        $this->actingAs($this->user('staff'))->get('/admin/theme')->assertForbidden();
    }

    // ================= pages & menus =================

    public function test_pages_are_published_escaped_and_translated(): void
    {
        $this->actingAs($this->owner());
        $this->post('/admin/pages', ['title' => 'About us', 'body' => "We sell things.\n<script>alert(1)</script>", 'is_published' => 1, 'tr' => ['ur' => ['title' => 'ہمارے بارے میں', 'body' => 'ہم چیزیں بیچتے ہیں']]])->assertRedirect('/admin/pages');
        $page = Page::firstOrFail();
        $this->assertSame('about-us', $page->slug);

        auth()->logout();
        $this->get('/page/about-us')->assertOk()->assertSee('We sell things.')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->withSession(['locale' => 'ur'])->get('/page/about-us')->assertSee('ہم چیزیں بیچتے ہیں');
        $this->get('/sitemap.xml')->assertSee(route('store.page', 'about-us'));

        $page->update(['is_published' => false]);
        $this->get('/page/about-us')->assertNotFound();
        $this->get('/page/missing')->assertNotFound();

        $this->post('/admin/pages', ['title' => 'عمومی صفحہ', 'is_published' => 1]);   // an Urdu-only title still gets a slug
        $this->assertNotEmpty(Page::latest('id')->first()->slug);
    }

    public function test_menu_links_show_in_the_store_and_are_safe(): void
    {
        $page = Page::create(['title' => 'Contact', 'body' => 'x', 'is_published' => true]);
        $cat = Category::create(['name' => 'Shoes', 'is_active' => true]);
        $this->actingAs($this->owner());

        $this->post('/admin/menu-items', ['location' => 'header', 'label' => 'Offers', 'type' => 'url', 'url' => '/shop?sort=price_asc', 'is_active' => 1, 'tr' => ['ur' => ['label' => 'پیشکشیں']]])->assertRedirect('/admin/menu-items');
        $this->post('/admin/menu-items', ['location' => 'footer', 'label' => 'Reach us', 'type' => 'page', 'page_id' => $page->id, 'is_active' => 1])->assertRedirect();
        $this->post('/admin/menu-items', ['location' => 'header', 'label' => 'Footwear', 'type' => 'category', 'category_id' => $cat->id, 'is_active' => 1])->assertRedirect();
        $this->post('/admin/menu-items', ['location' => 'header', 'label' => 'Bad', 'type' => 'url', 'url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');
        $this->post('/admin/menu-items', ['location' => 'header', 'label' => 'Bad', 'type' => 'page'])->assertSessionHasErrors('page_id');

        auth()->logout();
        $this->get('/')->assertSee('Offers')->assertSee('/shop?sort=price_asc', false)->assertSee('Footwear')->assertSee('Reach us')->assertSee(route('store.page', 'contact'));
        $this->withSession(['locale' => 'ur'])->get('/')->assertSee('پیشکشیں');

        $offers = MenuItem::firstWhere('label', 'Offers');
        $this->actingAs($this->owner())->post("/admin/menu-items/{$offers->id}/toggle");
        auth()->logout();
        $this->get('/')->assertDontSee('Offers');

        $page->update(['is_published' => false]);   // a link to a hidden page disappears by itself
        $this->get('/')->assertDontSee('Reach us');

        $this->actingAs($this->owner());
        $second = MenuItem::firstWhere('label', 'Footwear');
        $this->post("/admin/menu-items/{$second->id}/move", ['dir' => 'up']);
        $this->assertSame(['Footwear', 'Offers'], MenuItem::where('location', 'header')->orderBy('sort_order')->pluck('label')->all());
        $this->get('/admin/menu-items')->assertOk()->assertSee('Footwear');

        $page->delete();
        $this->assertNull(MenuItem::firstWhere('label', 'Reach us')->href());   // the link stays but points nowhere, so it is hidden
    }

    // ================= e-mails =================

    public function test_email_wording_can_be_customised_per_language(): void
    {
        $this->actingAs($this->owner());
        $this->get('/admin/emails')->assertOk();
        $this->put('/admin/emails', ['status_updates' => 1, 't' => ['order_intro' => ['en' => 'Thanks :name, order :number is in!', 'ur' => 'شکریہ :name، آرڈر :number مل گیا!'], 'order_footer' => ['en' => 'Call us any time.']]])->assertRedirect('/admin/emails');

        $order = $this->order();
        $html = (new OrderPlaced($order))->render();
        $this->assertStringContainsString("Thanks Ali Khan, order {$order->number} is in!", $html);
        $this->assertStringContainsString('Call us any time.', $html);

        app()->setLocale('ur');
        $this->assertStringContainsString("شکریہ Ali Khan، آرڈر {$order->number} مل گیا!", (new OrderPlaced($order))->render());
        app()->setLocale('ur_Latn');
        $this->assertStringContainsString("Thanks Ali Khan, order {$order->number} is in!", (new OrderPlaced($order))->render());   // falls back to English wording
    }

    public function test_customers_get_status_emails_in_their_language_and_can_switch_them_off(): void
    {
        $this->withSession(['locale' => 'ur']);
        $this->get('/');
        $order = $this->order();
        $this->assertSame('ur', $order->locale);

        $svc = app(OrderService::class);
        $svc->transition($order->fresh(), 'confirmed');
        Mail::assertSent(OrderStatusChanged::class, fn ($m) => $m->hasTo('ali@example.com') && $m->locale === 'ur');

        Mail::fake();
        $svc->transition($order->fresh(), 'preparing');            // not a milestone customers need to hear about
        Mail::assertNothingSent();

        $svc->transition($order->fresh(), 'out_for_delivery');
        Mail::assertSent(OrderStatusChanged::class, 1);
        $html = (new OrderStatusChanged($order->fresh()))->render();
        $this->assertStringContainsString($order->number, $html);

        Mail::fake();
        setting()->set('emails.status_updates', false);
        $svc->transition($order->fresh(), 'completed');
        Mail::assertNothingSent();
    }

    // ================= reports =================

    public function test_reports_add_up_and_ignore_cancelled_orders(): void
    {
        $a = $this->product(['name' => 'Alpha', 'price' => 1000, 'category_id' => Category::create(['name' => 'Cat A', 'is_active' => true])->id]);
        $b = $this->product(['name' => 'Beta', 'price' => 500]);
        Coupon::create(['code' => 'TEN', 'type' => 'fixed', 'value' => 100, 'is_active' => true]);

        $o1 = $this->order(null, ['coupon' => 'TEN'], ['product' => $a, 'qty' => 2]);   // 2000 − 100 + 100 shipping = 2000
        $o2 = $this->order(null, ['email' => 'sara@example.com', 'name' => 'Sara'], ['product' => $b, 'qty' => 1]);   // 600
        $o3 = $this->order(null, [], ['product' => $b, 'qty' => 4]);
        app(OrderService::class)->transition($o3, 'cancelled');

        $this->actingAs($this->owner());
        $res = $this->get('/admin/reports')->assertOk();
        $res->assertSee('Rs 2,600.00')->assertSee('Alpha')->assertSee('Cat A')->assertSee('TEN')->assertSee('Best sellers');
        $this->assertEquals(2600, $res->viewData('kpi')['revenue']);
        $this->assertSame(2, $res->viewData('kpi')['orders']);
        $this->assertSame(1300.0, $res->viewData('kpi')['aov']);
        $this->assertSame(2, $res->viewData('kpi')['customers']);
        $this->assertSame('Alpha', $res->viewData('top')[0]['name']);
        $this->assertSame([2, 1], [$res->viewData('statuses')['pending'] ?? 0, $res->viewData('statuses')['cancelled'] ?? 0]);
        $this->assertArrayNotHasKey('Beta', $res->viewData('categories'));   // uncategorised sales are grouped separately

        $this->get('/admin/reports?range=7')->assertOk();
        $this->get('/admin/reports?range=custom&from=2020-01-01&to=2020-01-31')->assertOk()->assertSee('No sales yet.');
        $this->get('/admin/reports?range=custom&from=2020-02-01&to=2020-01-01')->assertOk();   // reversed dates fall back to 30 days
        $this->get('/admin/reports?range=nope')->assertSessionHasErrors('range');
    }

    public function test_reports_csv_export_and_permissions(): void
    {
        $this->order(null, ['name' => '=HYPERLINK("x")']);
        $this->actingAs($this->owner());

        $csv = $this->get('/admin/reports/export')->assertOk()->streamedContent();
        $this->assertStringStartsWith('number,placed_at,customer', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);   // formula injection neutralised
        $this->assertStringContainsString('1100.00', $csv);

        $this->actingAs($this->user('staff'))->get('/admin/reports')->assertForbidden();
        $this->actingAs($this->user('manager'))->get('/admin/reports')->assertOk();
    }

    // ================= reviews =================

    public function test_only_completed_orders_can_be_reviewed_once(): void
    {
        $order = $this->order();
        $item = $order->items()->first();
        $url = "/order/{$order->number}/{$order->public_token}/review/{$item->id}";
        $data = ['rating' => 5, 'name' => 'Ali', 'title' => 'Great', 'body' => 'Loved it'];

        $this->post($url, $data)->assertNotFound();   // not delivered yet

        app(OrderService::class)->transition($order->fresh(), 'confirmed');
        foreach (['preparing', 'out_for_delivery', 'completed'] as $s) {
            app(OrderService::class)->transition($order->fresh(), $s);
        }
        $this->get($order->trackUrl())->assertSee('Rate this product');
        $this->post($url, ['rating' => 9] + $data)->assertSessionHasErrors('rating');
        $this->post("/order/{$order->number}/wrongtoken/review/{$item->id}", $data)->assertNotFound();

        $this->post($url, $data)->assertRedirect($order->trackUrl())->assertSessionHas('success');
        $review = Review::firstOrFail();
        $this->assertSame(['pending', 5, $order->id], [$review->status, $review->rating, $review->order_id]);
        $this->post($url, $data)->assertSessionHas('error');   // once per purchase
        $this->assertSame(1, Review::count());
    }

    public function test_reviews_are_moderated_and_shown_with_ratings(): void
    {
        $p = $this->product(['name' => 'Lamp']);
        $order = $this->order(null, [], ['product' => $p], 'completed');
        $item = $order->items()->first();
        $this->post("/order/{$order->number}/{$order->public_token}/review/{$item->id}", ['rating' => 4, 'name' => 'Ali', 'title' => 'Bright', 'body' => 'Very bright lamp']);

        $this->get('/product/'.$p->slug)->assertDontSee('Very bright lamp');   // pending reviews stay private
        $review = Review::firstOrFail();

        $admin = $this->owner();
        $this->actingAs($admin)->get('/admin/reviews')->assertOk()->assertSee('Lamp')->assertSee('Very bright');
        $this->get("/admin/reviews/{$review->id}/edit")->assertOk();
        $this->put("/admin/reviews/{$review->id}", ['status' => 'approved', 'reply' => 'Thank you!'])->assertRedirect();
        auth()->logout();

        $this->get('/product/'.$p->slug)->assertSee('Very bright lamp')->assertSee('Verified buyer')->assertSee('Thank you!')->assertSee('"ratingValue":"4"', false)->assertSee('"reviewCount":"1"', false);
        $this->get('/shop')->assertDontSee('4.0 (1)');   // cards no longer carry a star row

        $this->actingAs($admin)->put("/admin/reviews/{$review->id}", ['status' => 'hidden']);
        auth()->logout();
        $this->get('/product/'.$p->slug)->assertDontSee('Very bright lamp');

        $this->actingAs($admin)->delete("/admin/reviews/{$review->id}")->assertRedirect();
        $this->assertSame(0, Review::count());
        $this->get('/admin/reviews?status=bogus')->assertNotFound();
    }

    public function test_reviews_can_publish_automatically_and_follow_the_module(): void
    {
        setting()->set('growth.reviews_auto', true);
        $order = $this->order(null, [], [], 'completed');
        $this->post("/order/{$order->number}/{$order->public_token}/review/{$order->items()->first()->id}", ['rating' => 5, 'name' => 'Ali']);
        $this->assertSame('approved', Review::first()->status);

        BusinessProfile::apply('pharmacy');   // fast food has no reviews module
        setting()->set('modules.reviews', false);
        $this->actingAs($this->owner())->get('/admin/reviews')->assertNotFound();
        auth()->logout();
        $this->get('/product/'.Product::first()->slug)->assertOk()->assertDontSee('Customer reviews');
        $this->post("/order/{$order->number}/{$order->public_token}/review/{$order->items()->first()->id}", ['rating' => 5, 'name' => 'Ali'])->assertNotFound();
    }

    // ================= abandoned checkouts =================

    public function test_cart_is_captured_only_with_an_email_and_a_cart(): void
    {
        $this->postJson('/checkout/capture', ['email' => 'nida@example.com'])->assertOk()->assertJson(['ok' => false]);   // empty cart
        $p = $this->product();
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 2]);

        $this->postJson('/checkout/capture', ['email' => 'not-an-email'])->assertStatus(422);
        $this->postJson('/checkout/capture', ['email' => 'Nida@Example.com'])->assertJson(['ok' => true]);
        $row = AbandonedCheckout::firstOrFail();
        $this->assertSame(['nida@example.com', 2, '2000.00'], [$row->email, $row->items_count, $row->total]);

        $this->postJson('/checkout/capture', ['email' => 'nida@example.com']);   // same person: updated, not duplicated
        $this->assertSame(1, AbandonedCheckout::count());

        setting()->set('growth.abandoned', false);
        $this->postJson('/checkout/capture', ['email' => 'other@example.com'])->assertJson(['ok' => false]);
        $this->assertSame(1, AbandonedCheckout::count());
    }

    public function test_reminders_go_out_once_after_the_delay_and_respect_opt_out(): void
    {
        $mk = fn (string $email, array $o = []) => AbandonedCheckout::create($o + ['email' => $email, 'token' => str_repeat(substr(md5($email), 0, 8), 5), 'items' => [], 'total' => 500, 'items_count' => 1, 'last_seen_at' => now()->subHours(3)]);
        $due = $mk('due@example.com');
        $mk('fresh@example.com', ['last_seen_at' => now()->subMinutes(20)]);
        $mk('done@example.com', ['recovered_at' => now()]);
        $mk('stop@example.com', ['opted_out' => true]);
        $mk('old@example.com', ['last_seen_at' => now()->subDays(9)]);
        $mk('empty@example.com', ['items_count' => 0]);

        Artisan::call('nexcart:send-abandoned-reminders');
        Mail::assertSent(AbandonedCartReminder::class, 1);
        Mail::assertSent(AbandonedCartReminder::class, fn ($m) => $m->hasTo('due@example.com'));
        $this->assertNotNull($due->fresh()->reminded_at);

        Mail::fake();
        Artisan::call('nexcart:send-abandoned-reminders');   // never twice
        Mail::assertNothingSent();

        setting()->set('growth.abandoned', false);
        $mk('later@example.com');
        Artisan::call('nexcart:send-abandoned-reminders');
        Mail::assertNothingSent();

        $html = (new AbandonedCartReminder($due))->render();
        $this->assertStringContainsString($due->recoverUrl(), $html);
        $this->assertStringContainsString($due->stopUrl(), $html);
    }

    public function test_recovery_link_rebuilds_the_cart_and_ordering_marks_it_recovered(): void
    {
        $p = $this->product(['name' => 'Kettle']);
        $gone = $this->product(['name' => 'Gone']);
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 2]);
        $this->postJson('/cart/add', ['product_id' => $gone->id, 'qty' => 1]);
        $this->postJson('/checkout/capture', ['email' => 'ali@example.com']);
        $row = AbandonedCheckout::firstOrFail();

        $this->flushSession();
        $gone->update(['status' => 'archived']);
        $this->get($row->recoverUrl())->assertRedirect('/checkout')->assertSessionHas('notice');
        $this->getJson('/cart.json')->assertJsonPath('count', 2)->assertJsonPath('lines.0.name', 'Kettle');
        $this->get('/cart/recover/'.str_repeat('z', 40))->assertNotFound();

        $order = app(CheckoutService::class)->place(['name' => 'Ali', 'email' => 'ALI@example.com', 'phone' => '0300111222', 'address_line' => 'x', 'city' => 'Karachi', 'shipping_rate_id' => ShippingZone::first()->rates()->first()->id, 'payment_method' => 'cod']);
        $this->assertNotNull($row->fresh()->recovered_at);
        $this->assertSame($order->email, 'ALI@example.com');

        $other = AbandonedCheckout::create(['email' => 'x@example.com', 'token' => str_repeat('a', 40), 'items' => [], 'total' => 1, 'items_count' => 1, 'last_seen_at' => now()]);
        $this->get($other->stopUrl())->assertRedirect('/')->assertSessionHas('notice');
        $this->assertTrue($other->fresh()->opted_out);
    }

    // ================= sidebar badges & WhatsApp =================

    public function test_admin_shows_pending_counts_and_a_whatsapp_link(): void
    {
        $o = $this->order(null, ['phone' => '0300 1234567']);
        $this->actingAs($this->owner());

        $this->get('/admin')->assertOk()->assertSee('nx-nav-badge', false);
        $html = $this->get("/admin/orders/{$o->id}")->assertOk()->assertSee('Message on WhatsApp')->getContent();
        $this->assertStringContainsString('https://wa.me/923001234567', $html);

        setting()->set('store.phone_code', '44');
        $this->get("/admin/orders/{$o->id}")->assertSee('https://wa.me/443001234567', false);

        $intl = $this->order(null, ['email' => 'b@example.com', 'phone' => '+971 50 123 4567']);
        $this->get("/admin/orders/{$intl->id}")->assertSee('https://wa.me/971501234567', false);
    }

    public function test_growth_settings_validate_and_save(): void
    {
        $this->actingAs($this->owner());
        $base = ['store' => ['name' => 'X', 'currency' => 'PKR', 'currency_symbol' => 'Rs', 'timezone' => 'Asia/Karachi', 'default_locale' => 'en', 'locales' => ['en']], 'theme' => ['primary' => '#112233', 'accent' => '#445566', 'mode' => 'dark']];

        $this->put('/admin/settings', array_replace_recursive($base, ['store' => ['phone_code' => 'abc']]))->assertSessionHasErrors('store.phone_code');
        $this->put('/admin/settings', $base + ['growth' => ['abandoned_hours' => 500]])->assertSessionHasErrors('growth.abandoned_hours');

        $this->put('/admin/settings', array_replace_recursive($base, ['store' => ['phone_code' => '971']]) + ['growth' => ['abandoned' => 1, 'abandoned_hours' => 4, 'reviews_auto' => 1]])->assertRedirect();
        $this->assertEquals([4, '971'], [setting('growth.abandoned_hours'), setting('store.phone_code')]);
        $this->assertTrue(setting('growth.reviews_auto'));
        $this->put('/admin/settings', $base)->assertRedirect();
        $this->assertFalse(setting('growth.reviews_auto'));   // unchecked switches turn things off
    }

    public function test_setup_creates_starter_pages_and_footer_links(): void
    {
        setting()->set('system.installed', false);
        $this->post('/setup', ['store_name' => 'Fresh', 'currency' => 'PKR', 'currency_symbol' => 'Rs', 'timezone' => 'Asia/Karachi', 'name' => 'Owner', 'email' => 'o@example.com', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])->assertRedirect();

        $this->assertSame(['About us', 'Delivery & returns', 'Privacy policy'], Page::orderBy('id')->pluck('title')->all());
        $this->assertSame(3, MenuItem::where('location', 'footer')->count());
        auth()->logout();
        $this->get('/')->assertSee('Privacy policy');
        $this->withSession(['locale' => 'ur'])->get('/')->assertSee('پرائیویسی پالیسی');
    }
}
