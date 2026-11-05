<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\ModifierGroup;
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
        BusinessProfile::apply('general');
    }

    private function product(array $o = []): Product
    {
        $p = new Product;
        $p->forceFill($o + ['name' => 'Widget', 'price' => 100, 'status' => 'active', 'track_stock' => true, 'stock_qty' => 10])->save();

        return $p;
    }

    public function test_home_renders_default_sections_in_each_language(): void
    {
        $this->get('/')->assertOk()->assertSee('Everything you need, delivered')->assertSee('mk-hero', false)->assertSee('Fast delivery');

        $this->withSession(['locale' => 'ur'])->get('/')->assertOk()->assertSee('dir="rtl"', false)->assertSee('جو کچھ آپ کو چاہیے')->assertSee('تیز ڈیلیوری');
        $this->withSession(['locale' => 'ur_Latn'])->get('/')->assertSee('Tez delivery');
    }

    public function test_hidden_sections_are_not_shown_and_products_row_respects_source(): void
    {
        HomeSection::where('type', 'hero')->update(['is_active' => false]);
        $this->get('/')->assertDontSee('sf-hero', false);

        $a = $this->product(['name' => 'Plain One']);
        $b = $this->product(['name' => 'Star One', 'is_featured' => true]);
        $this->product(['name' => 'Draft One', 'status' => 'draft']);
        HomeSection::where('type', 'products')->first()->update(['settings' => ['title' => 'Stars', 'source' => 'featured', 'limit' => 8]]);

        $this->get('/')->assertSee('Star One')->assertDontSee('Plain One')->assertDontSee('Draft One');
    }

    public function test_shop_lists_only_active_products_and_filters(): void
    {
        $drinks = Category::create(['name' => 'Drinks']);
        $cold = Category::create(['name' => 'Cold', 'parent_id' => $drinks->id]);
        $acme = Brand::create(['name' => 'Acme']);
        $this->product(['name' => 'Cola', 'price' => 50, 'category_id' => $cold->id, 'brand_id' => $acme->id]);
        $this->product(['name' => 'Water', 'price' => 20, 'category_id' => $cold->id, 'stock_qty' => 0]);
        $this->product(['name' => 'Gadget', 'price' => 900]);
        $this->product(['name' => 'Hidden', 'status' => 'draft']);

        $this->get('/shop')->assertOk()->assertSee('Cola')->assertSee('Gadget')->assertDontSee('Hidden');
        // parent category includes sub-category products
        $this->get('/category/'.$drinks->slug)->assertSee('Cola')->assertSee('Water')->assertDontSee('Gadget');
        $this->get('/shop?brand[]='.$acme->id)->assertSee('Cola')->assertDontSee('Gadget');
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
        BusinessProfile::apply('fastfood');
        CatalogAttribute::where('key', 'spice_level')->update(['is_filterable' => true]);
        $this->product(['name' => 'Hot Wings', 'specs' => ['spice_level' => 'Hot']]);
        $this->product(['name' => 'Mild Wings', 'specs' => ['spice_level' => 'Mild']]);

        $this->get('/shop?spec[spice_level]=Hot')->assertSee('Hot Wings')->assertDontSee('Mild Wings');
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
        $this->get('/product/'.$p->slug.'/quick')->assertOk()->assertSee('data-buy', false);
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

    public function test_variants_and_modifiers_are_priced_server_side(): void
    {
        BusinessProfile::apply('fastfood');
        $p = $this->product(['name' => 'Pizza', 'type' => 'variable', 'price' => 0, 'stock_qty' => 0]);
        $small = $p->variants()->create(['name' => 'Small', 'price' => 500, 'stock_qty' => 5]);
        $large = $p->variants()->create(['name' => 'Large', 'price' => 900, 'stock_qty' => 1]);
        $group = ModifierGroup::create(['name' => 'Extras', 'min_select' => 1, 'max_select' => 2]);
        $cheese = $group->options()->create(['name' => 'Cheese', 'price_delta' => 80]);
        $olives = $group->options()->create(['name' => 'Olives', 'price_delta' => 30]);
        $mushroom = $group->options()->create(['name' => 'Mushroom', 'price_delta' => 40]);
        $p->modifierGroups()->attach($group);

        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 1, 'options' => [$cheese->id]])->assertStatus(422)->assertJsonValidationErrors('variant_id');
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $small->id, 'qty' => 1])->assertStatus(422)->assertJsonValidationErrors('options');
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $small->id, 'qty' => 1, 'options' => [$cheese->id, $olives->id, $mushroom->id]])->assertStatus(422);

        // 500 + 80 + 30 = 610 each, x2 = 1220
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $small->id, 'qty' => 2, 'options' => [$cheese->id, $olives->id], 'price' => 1])
            ->assertOk()->assertJsonPath('subtotal', 'Rs 1,220.00')->assertJsonPath('lines.0.variant', 'Small');

        // other options of the same product with different add-ons are a different line; stock is shared across lines
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $small->id, 'qty' => 3, 'options' => [$mushroom->id]])->assertOk()->assertJsonCount(2, 'lines');
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $small->id, 'qty' => 1, 'options' => [$olives->id]])->assertStatus(422);
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $large->id, 'qty' => 2, 'options' => [$cheese->id]])->assertStatus(422);
        // a variant id from another product is rejected
        $other = $this->product(['name' => 'Other', 'type' => 'variable'])->variants()->create(['name' => 'X', 'price' => 1, 'stock_qty' => 9]);
        $this->postJson('/cart/add', ['product_id' => $p->id, 'variant_id' => $other->id, 'qty' => 1, 'options' => [$cheese->id]])->assertStatus(422);
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

    public function test_wishlist_toggle_and_page(): void
    {
        $p = $this->product(['name' => 'Loved']);

        $this->postJson('/wishlist/'.$p->id)->assertJson(['added' => true, 'count' => 1]);
        $this->get('/wishlist')->assertOk()->assertSee('Loved');
        $this->postJson('/wishlist/'.$p->id)->assertJson(['added' => false, 'count' => 0]);
        $this->get('/wishlist')->assertSee('Nothing saved yet');
        $this->postJson('/wishlist/9999')->assertNotFound();
    }

    public function test_layout_has_theme_language_and_cart_drawer(): void
    {
        $this->get('/shop')->assertOk()->assertSee('sfDrawer', false)->assertSee('data-theme-toggle', false)->assertSee('rel="manifest"', false)->assertSee('English');
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
        $this->assertSame([$ids[1], $ids[0], $ids[2], $ids[3]], HomeSection::orderBy('sort_order')->pluck('id')->all());

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
}
