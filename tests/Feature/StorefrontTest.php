<?php

namespace Tests\Feature;

use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\User;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
    }

    private function product(array $o = []): Product
    {
        $p = new Product;
        $p->forceFill($o + ['name' => 'Widget', 'price' => 100, 'status' => 'active', 'track_stock' => true, 'stock_qty' => 10])->save();

        return $p;
    }

    public function test_home_renders_default_sections_in_each_language(): void
    {
        $this->get('/')->assertOk()->assertSee('Your pharmacy,', false)->assertSee('now online')->assertSee('mk-hero', false)->assertSee('Fast delivery');

        $this->withSession(['locale' => 'ur'])->get('/')->assertOk()->assertSee('dir="rtl"', false)->assertSee('آپ کی فارمیسی،', false)->assertSee('اب آن لائن')->assertSee('تیز ڈیلیوری');
        $this->withSession(['locale' => 'ur_Latn'])->get('/')->assertSee('Tez delivery');
    }

    public function test_hidden_sections_are_not_shown_and_products_row_respects_source(): void
    {
        HomeSection::where('type', 'hero')->update(['is_active' => false]);
        $this->get('/')->assertDontSee('sf-hero', false);

        $a = $this->product(['name' => 'Plain One']);
        $b = $this->product(['name' => 'Star One', 'is_featured' => true]);
        $this->product(['name' => 'Draft One', 'status' => 'draft']);
        HomeSection::where('type', 'products')->update(['settings' => ['title' => 'Stars', 'source' => 'featured', 'limit' => 8]]);

        $this->get('/')->assertSee('Star One')->assertDontSee('Plain One')->assertDontSee('Draft One');
    }

    public function test_shop_lists_only_active_products_and_filters(): void
    {
        $drinks = Category::create(['name' => 'Drinks']);
        $cold = $drinks;
        $this->product(['name' => 'Cola', 'price' => 50, 'category_id' => $cold->id]);
        $this->product(['name' => 'Water', 'price' => 20, 'category_id' => $cold->id, 'stock_qty' => 0]);
        $this->product(['name' => 'Gadget', 'price' => 900]);
        $this->product(['name' => 'Hidden', 'status' => 'draft']);

        $this->get('/shop')->assertOk()->assertSee('Cola')->assertSee('Gadget')->assertDontSee('Hidden');
        $this->get('/category/'.$drinks->slug)->assertSee('Cola')->assertSee('Water')->assertDontSee('Gadget');
        $this->get('/shop?price_min=30&price_max=100')->assertSee('Cola')->assertDontSee('Water')->assertDontSee('Gadget');
        $this->get('/shop?in_stock=1')->assertSee('Cola')->assertDontSee('Water');
        $this->get('/shop?q=gad')->assertSee('Gadget')->assertDontSee('Cola');
        $this->get('/category/nope')->assertNotFound();

        $html = $this->get('/shop?sort=price_desc')->getContent();
        $this->assertTrue(strpos($html, 'Gadget') < strpos($html, 'Cola'));
        $this->get('/shop?sort=hack')->assertSessionHasErrors('sort');
    }

    public function test_attribute_filters_use_filterable_fields(): void
    {
        BusinessProfile::apply('pharmacy');
        CatalogAttribute::where('key', 'dosage_form')->update(['is_filterable' => true]);
        $this->product(['name' => 'Tablet Alpha', 'specs' => ['dosage_form' => 'Tablet']]);
        $this->product(['name' => 'Syrup Beta', 'specs' => ['dosage_form' => 'Syrup']]);

        $this->get('/shop?spec[dosage_form]=Tablet')->assertSee('Tablet Alpha')->assertDontSee('Syrup Beta');
    }

    public function test_instant_search_matches_names_and_urdu_translations(): void
    {
        $this->product(['name' => 'Panadol', 'translations' => ['ur' => ['name' => 'پینا ڈول']]]);

        $this->getJson('/search/suggest?q=pana')->assertOk()->assertJsonPath('results.0.name', 'Panadol');
        $this->getJson('/search/suggest?q='.urlencode('پینا'))->assertJsonCount(1, 'results');
        $this->getJson('/search/suggest?q=p')->assertJson(['results' => []]);
        $this->getJson('/search/suggest?q=zzzz')->assertJsonCount(0, 'results');
    }

    public function test_product_page_quick_view_and_seo(): void
    {
        $p = $this->product(['name' => 'Lamp', 'short_description' => 'Bright', 'sku' => 'L-1']);
        $this->product(['name' => 'Draft Lamp', 'status' => 'draft', 'slug' => 'draft-lamp']);

        $this->get('/product/'.$p->slug)->assertOk()->assertSee('Lamp')->assertSee('application/ld+json', false)->assertSee('"price":"100.00"', false)
            ->assertSee('og:title', false)->assertSee('rel="canonical"', false);
        $this->get('/product/'.$p->slug.'/quick', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertSee('data-buy', false);
        $this->get('/product/'.$p->slug.'/quick')->assertRedirect('/product/'.$p->slug);   // opened directly: real page, not a bare fragment
        $this->get('/product/draft-lamp')->assertNotFound();
        $this->get('/product/missing')->assertNotFound();
    }

    public function test_urdu_product_page_uses_translated_text(): void
    {
        $p = $this->product(['name' => 'Lamp', 'translations' => ['ur' => ['name' => 'چراغ']]]);
        $this->withSession(['locale' => 'ur'])->get('/product/'.$p->slug)->assertSee('چراغ');
    }

    public function test_seo_endpoints(): void
    {
        $p = $this->product(['name' => 'Lamp']);
        $c = Category::create(['name' => 'Home']);

        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('store.product', $p->slug))->assertSee(route('store.category', $c->slug));
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('sitemap.xml');
        $this->get('/manifest.webmanifest')->assertOk()->assertJsonPath('display', 'standalone')->assertJsonPath('icons.0.sizes', '192x192');
        $this->assertFileExists(public_path('sw.js'));
    }

    public function test_cart_add_update_remove_and_totals(): void
    {
        $p = $this->product(['price' => 150]);

        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 2])->assertOk()->assertJsonPath('count', 2)->assertJsonPath('subtotal', 'Rs 300.00');
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 1])->assertJsonPath('count', 3);   // same line accumulates

        $key = $this->getJson('/cart.json')->json('lines.0.key');
        $this->patchJson("/cart/{$key}", ['qty' => 5])->assertJsonPath('count', 5)->assertJsonPath('subtotal', 'Rs 750.00');
        $this->deleteJson("/cart/{$key}")->assertJsonPath('count', 0);
        $this->get('/cart')->assertOk()->assertSee('Your cart is empty');
    }

    public function test_cart_never_exceeds_stock_and_rejects_bad_products(): void
    {
        $p = $this->product(['stock_qty' => 3]);
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 4])->assertStatus(422)->assertJsonValidationErrors('qty');
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 3])->assertOk();
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 1])->assertStatus(422);

        $out = $this->product(['name' => 'Gone', 'stock_qty' => 0]);
        $this->postJson('/cart/add', ['product_id' => $out->id, 'qty' => 1])->assertStatus(422);

        $draft = $this->product(['name' => 'Draft', 'status' => 'draft']);
        $this->postJson('/cart/add', ['product_id' => $draft->id, 'qty' => 1])->assertNotFound();
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 0])->assertStatus(422);
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 500])->assertStatus(422);
    }

    public function test_variants_are_priced_server_side(): void
    {
        BusinessProfile::apply('pharmacy');
        $p = $this->product(['name' => 'Brufen', 'type' => 'variable', 'price' => 0, 'stock_qty' => 0]);
        $small = $p->variants()->create(['name' => '10 tablets', 'price' => 500, 'stock_qty' => 5]);
        $large = $p->variants()->create(['name' => '30 tablets', 'price' => 900, 'stock_qty' => 1]);

        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 1])->assertStatus(422)->assertJsonValidationErrors('variant_id');

        // the price always comes from the server, never from the request
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $small->id, 'qty' => 2, 'price' => 1])
            ->assertOk()->assertJsonPath('subtotal', 'Rs 1,000.00')->assertJsonPath('lines.0.variant', '10 tablets');

        // another pack size is another line; stock is checked per variant
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $large->id, 'qty' => 1])->assertOk()->assertJsonCount(2, 'lines');
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $large->id, 'qty' => 1])->assertStatus(422);
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $small->id, 'qty' => 9])->assertStatus(422);
        // a variant id from another product is rejected
        $other = $this->product(['name' => 'Other', 'type' => 'variable'])->variants()->create(['name' => 'X', 'price' => 1, 'stock_qty' => 9]);
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $other->id, 'qty' => 1])->assertStatus(422);
    }

    public function test_cart_follows_price_changes_and_drops_unavailable_products(): void
    {
        $p = $this->product(['price' => 100]);
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 1]);

        $p->update(['price' => 120]);
        $this->getJson('/cart.json')->assertJsonPath('subtotal', 'Rs 120.00');

        $p->update(['status' => 'archived']);
        $this->getJson('/cart.json')->assertJsonPath('count', 0);
    }

    public function test_layout_has_theme_language_and_cart_drawer(): void
    {
        $this->get('/shop')->assertOk()->assertSee('sfDrawer', false)->assertSee('rel="manifest"', false)->assertSee('English')->assertDontSee('data-theme-toggle', false);   // marketplace look is light-only

        setting()->set('sf.design.marketplace', false);
        $this->get('/shop')->assertOk()->assertSee('data-theme-toggle', false);
    }

    public function test_admin_can_manage_home_sections(): void
    {
        $owner = tap(User::factory()->create(), fn ($u) => $u->assignRole('owner'));
        $this->actingAs($owner);

        $this->get('/admin/home-sections')->assertOk()->assertSee('Hero banner');
        $this->get('/admin/home-sections/create?type=banner')->assertOk();
        $this->get('/admin/home-sections/create?type=nope')->assertNotFound();

        $this->post('/admin/home-sections', [
            'type' => 'banner', 'title' => 'Eid Sale', 'subtitle' => '50% off', 'button_label' => 'Shop', 'button_url' => '/shop?sale=1', 'is_active' => 1,
            'tr' => ['ur' => ['title' => 'عید سیل'], 'xx' => ['title' => 'bad']],
        ])->assertRedirect('/admin/home-sections');

        $s = HomeSection::firstWhere('title', 'Eid Sale');
        $this->assertSame(['ur' => ['title' => 'عید سیل']], $s->translations);
        $this->get('/')->assertSee('Eid Sale');
        $this->withSession(['locale' => 'ur'])->get('/')->assertSee('عید سیل');

        // links must be local paths or http(s) — never javascript:
        $this->post('/admin/home-sections', ['type' => 'banner', 'title' => 'Bad', 'button_url' => 'javascript:alert(1)'])->assertSessionHasErrors('button_url');
        $this->post('/admin/home-sections', ['type' => 'products', 'source' => 'category'])->assertSessionHasErrors('category_id');
        $this->post('/admin/home-sections', ['type' => 'text', 'body' => "Line one\n<script>x</script>", 'is_active' => 1])->assertRedirect();
        $this->get('/')->assertDontSee('<script>x</script>', false)->assertSee('&lt;script&gt;x&lt;/script&gt;', false);

        $this->put("/admin/home-sections/{$s->id}", ['title' => 'Eid Sale 2', 'subtitle' => '', 'button_url' => '/shop'])->assertRedirect();
        $this->assertSame('Eid Sale 2', $s->fresh()->title);
        $this->assertFalse($s->fresh()->is_active);   // unchecked switch = hidden
    }

    public function test_sections_can_be_reordered_toggled_and_deleted(): void
    {
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('manager')));
        $ids = HomeSection::orderBy('sort_order')->pluck('id')->all();

        $this->post("/admin/home-sections/{$ids[1]}/move", ['dir' => 'up']);
        $this->assertSame([$ids[1], $ids[0], ...array_slice($ids, 2)], HomeSection::orderBy('sort_order')->pluck('id')->all());

        $this->post("/admin/home-sections/{$ids[1]}/move", ['dir' => 'up']);   // already first: no change, no error
        $this->assertSame($ids[1], HomeSection::orderBy('sort_order')->first()->id);

        $this->post("/admin/home-sections/{$ids[0]}/toggle");
        $this->assertFalse(HomeSection::find($ids[0])->is_active);

        $this->delete("/admin/home-sections/{$ids[3]}")->assertRedirect();
        $this->assertNull(HomeSection::find($ids[3]));
    }

    public function test_staff_without_permission_cannot_edit_home_page(): void
    {
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('staff')));
        $this->get('/admin/home-sections')->assertForbidden();
        $this->post('/admin/home-sections', ['type' => 'text', 'body' => 'x'])->assertForbidden();
    }

    public function test_only_categories_with_products_are_listed_and_shop_shows_twenty(): void
    {
        $full = Category::create(['name' => 'Fullcat']);
        Category::create(['name' => 'Emptycat']);
        for ($i = 1; $i <= 21; $i++) {
            $this->product(['name' => 'Item'.$i, 'category_id' => $full->id]);
        }
        $this->get('/shop')->assertOk()->assertSee('Fullcat')->assertDontSee('Emptycat');
        $this->get('/')->assertOk()->assertDontSee('Emptycat');
        $this->assertSame(20, \App\Models\Product::active()->paginate((int) sf('cards.per_page'))->count());
    }

    public function test_instant_search_returns_matching_categories_and_the_shop_finds_products_by_category_name(): void
    {
        $c = Category::create(['name' => 'Painrelief']);
        Category::create(['name' => 'Painempty']);
        $this->product(['name' => 'Zed', 'category_id' => $c->id]);
        $json = $this->getJson('/search/suggest?q=pain')->assertOk()->json();
        $this->assertSame(['Painrelief'], array_column($json['categories'], 'name'));
        $this->get('/shop?q=Painrelief')->assertSee('Zed');
    }

    public function test_footer_shows_contact_socials_categories_and_map_with_switches(): void
    {
        $c = Category::create(['name' => 'Footcat']);
        $this->product(['name' => 'P1', 'category_id' => $c->id]);
        setting()->setMany(['store.phone' => '0300 1112223', 'store.address' => '12 Mall Road, Lahore', 'store.email' => 'hi@shop.test', 'store.facebook' => 'https://facebook.com/x']);

        $r = $this->get('/')->assertOk()->assertSee('0300 1112223')->assertSee('12 Mall Road, Lahore')->assertSee('Footcat')->assertSee('https://facebook.com/x', false)
            ->assertSee('output=embed', false)->assertSee('https://maps.app.goo.gl/RPd2uKp3ua8RYgDx8', false)->assertDontSee('data-cookie-settings', false)->assertDontSee('data-fx-install', false);

        setting()->setMany(['sf.footer.show_phone' => false, 'sf.footer.show_map' => false, 'sf.footer.show_categories' => false, 'sf.footer.show_socials' => false]);
        $this->get('/')->assertDontSee('bi-telephone-fill', false)->assertDontSee('output=embed', false)->assertDontSee('ft-socials', false)->assertSee('12 Mall Road, Lahore');

        // a map link that is not Google Maps is never printed as an href
        setting()->setMany(['sf.footer.show_map' => true, 'sf.footer.map_url' => 'https://evil.test/maps']);
        $this->get('/')->assertDontSee('evil.test', false);
    }

    public function test_whatsapp_link_uses_the_country_code(): void
    {
        $this->assertSame('923004462436', \App\Support\Storefront::waNumber('0300 4462436'));
        $this->assertSame('923004462436', \App\Support\Storefront::waNumber('+92 300 4462436'));
        $this->assertSame('923004462436', \App\Support\Storefront::waNumber('03004462436'));
        $this->assertSame('923004462436', \App\Support\Storefront::waNumber('00923004462436'));
        setting()->set('store.whatsapp', '03004462436');
        $this->get('/')->assertSee('https://wa.me/923004462436', false);
    }

    public function test_cards_show_new_and_featured_badges_instead_of_genuine(): void
    {
        $this->product(['name' => 'Fresh item']);
        $old = $this->product(['name' => 'Old item']);
        \App\Models\Product::whereKey($old->id)->update(['created_at' => now()->subDays(90)]);
        $this->product(['name' => 'Star item', 'is_featured' => true]);

        $html = $this->get('/shop')->assertOk()->assertDontSee('Genuine product')->assertDontSee('mk-flag ok', false)->getContent();
        $this->assertSame(1, substr_count($html, 'mk-flag feat'));
        $this->assertSame(1, substr_count($html, 'mk-flag new'));   // Fresh item; Star item is featured, Old item is neither

        setting()->set('sf.cards.new_days', 0);
        $this->get('/shop')->assertDontSee('mk-flag new', false);
    }

    public function test_cards_warn_about_low_stock_only(): void
    {
        $this->product(['name' => 'Few left', 'track_stock' => true, 'stock_qty' => 3]);
        $this->product(['name' => 'Plenty', 'track_stock' => true, 'stock_qty' => 80]);

        $html = $this->get('/shop')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'class="mk-low"'));
        $this->assertStringContainsString('Only 3 left in stock.', $html);
    }

    public function test_cards_show_the_saving_and_a_stepper_that_follows_the_cart(): void
    {
        $sale = $this->product(['name' => 'On sale', 'price' => 80, 'compare_price' => 100]);
        $this->product(['name' => 'Plain', 'price' => 50]);

        $html = $this->get('/shop')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'class="mk-save"'));
        $this->assertStringContainsString('Save', $html);
        $this->assertStringContainsString('data-step="'.$sale->id.'"', $html);

        $cart = $this->postJson('/cart/add', ['product_id' => $sale->id, 'qty' => 1])->assertOk()->json();
        $this->assertSame($sale->id, $cart['lines'][0]['pid']);
        $key = $cart['lines'][0]['key'];
        $c2 = $this->patchJson('/cart/'.$key, ['qty' => 3])->assertOk()->json();
        $this->assertSame(3, $c2['lines'][0]['qty']);
        $c3 = $this->patchJson('/cart/'.$key, ['qty' => 0])->assertOk()->json();
        $this->assertSame([], $c3['lines']);
    }

    public function test_home_product_rows_use_the_slider_markup(): void
    {
        $p = $this->product(['name' => 'Slide me', 'is_featured' => true]);
        \App\Models\HomeSection::create(['type' => 'products', 'title' => 'Slider test', 'settings' => ['title' => 'Slider test', 'source' => 'featured', 'limit' => 8], 'sort_order' => 1, 'is_active' => true]);

        $this->get('/')->assertOk()->assertSee('data-pslider', false)->assertSee('data-ps-track', false)->assertSee('data-ps-prev', false)->assertSee('data-ps-bar', false)->assertSee('assets/js/product-slider.js', false)->assertSee('Slide me');
    }
}
