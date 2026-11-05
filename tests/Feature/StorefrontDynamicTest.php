<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\MenuItem;
use App\Models\Product;
use App\Models\User;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontDynamicTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('general');
        $this->owner = tap(User::factory()->create(), fn ($u) => $u->assignRole('owner'));
    }

    private function product(array $o = []): Product
    {
        $p = new Product;
        $p->forceFill($o + ['name' => 'Widget', 'price' => 100, 'status' => 'active', 'track_stock' => false])->save();

        return $p;
    }

    private function opts(array $v): void
    {
        $this->actingAs($this->owner)->put('/admin/storefront', ['v' => $v])->assertRedirect();
        auth()->logout();
    }

    public function test_switches_hide_header_footer_and_card_parts(): void
    {
        $this->product(['name' => 'Gizmo', 'compare_price' => 200, 'category_id' => Category::create(['name' => 'Tools'])->id]);
        $this->get('/')->assertSee('mk-topbar', false)->assertSee('mk-mega', false)->assertSee('data-compare-toggle', false)->assertSee('mk-top', false);
        $this->opts(['header_show_mega' => '0']);   // everything unticked except what we send => all other bools off
        $r = $this->get('/');
        $r->assertDontSee('mk-topbar', false)->assertDontSee('data-mega', false)->assertDontSee('data-compare-toggle', false)->assertDontSee('mk-cookie', false)->assertDontSee('id="mkTop"', false);
    }

    public function test_texts_and_footer_columns_follow_the_admin(): void
    {
        $this->opts(['topbar_enabled' => '1', 'topbar_text' => ['en' => 'Free delivery over 5000'], 'footer_shop_title' => ['en' => 'Browse'], 'header_show_deals' => '1', 'header_deals_label' => ['en' => 'Hot offers'], 'footer_show_legal' => '1', 'footer_copyright' => ['en' => 'All yours 2099'], 'cookie_enabled' => '1', 'cookie_accept_label' => ['en' => 'Sounds good']]);
        MenuItem::create(['location' => 'footer_shop', 'label' => 'Gift cards', 'type' => 'url', 'url' => '/shop?q=gift', 'is_active' => true, 'sort_order' => 1]);
        MenuItem::create(['location' => 'topbar', 'label' => 'Store finder', 'type' => 'url', 'url' => '/shop', 'is_active' => true, 'sort_order' => 1]);
        $this->opts(['topbar_enabled' => '1', 'topbar_show_links' => '1', 'topbar_text' => ['en' => 'Free delivery over 5000'], 'footer_shop_title' => ['en' => 'Browse'], 'header_show_deals' => '1', 'header_deals_label' => ['en' => 'Hot offers'], 'footer_copyright' => ['en' => 'All yours 2099'], 'cookie_enabled' => '1', 'cookie_accept_label' => ['en' => 'Sounds good']]);
        $this->get('/')->assertSee('Free delivery over 5000')->assertSee('Browse')->assertSee('Hot offers')->assertSee('Gift cards')->assertSee('Store finder')->assertSee('All yours 2099')->assertSee('Sounds good');
    }

    public function test_new_home_section_types_can_be_built_and_shown(): void
    {
        $this->actingAs($this->owner);
        $b = Brand::create(['name' => 'Acme', 'is_active' => true]);
        $this->product(['brand_id' => $b->id]);

        $this->post('/admin/home-sections', ['type' => 'steps', 'title' => 'Easy as 1-2-3', 'items' => [['icon' => 'search', 'title' => 'Find it', 'text' => 'Search the shop'], ['icon' => '', 'title' => 'Order it', 'text' => 'Pay on delivery'], ['title' => '', 'text' => 'dropped']], 'is_active' => 1])->assertRedirect('/admin/home-sections');
        $this->post('/admin/home-sections', ['type' => 'faq', 'title' => 'FAQ', 'items' => [['title' => 'Do you deliver?', 'text' => 'Yes, everywhere.']], 'is_active' => 1])->assertRedirect();
        $this->post('/admin/home-sections', ['type' => 'testimonials', 'items' => [['title' => 'Sara', 'role' => 'Lahore', 'text' => 'Great service!']], 'is_active' => 1])->assertRedirect();
        $this->post('/admin/home-sections', ['type' => 'brands', 'title' => 'Our brands', 'limit' => 6, 'is_active' => 1])->assertRedirect();
        $this->post('/admin/home-sections', ['type' => 'cta', 'title' => 'Join us today', 'button_label' => 'Start', 'button_url' => '/shop', 'is_active' => 1])->assertRedirect();
        $this->post('/admin/home-sections', ['type' => 'cta', 'title' => 'Bad', 'button_url' => 'javascript:alert(1)', 'is_active' => 1])->assertSessionHasErrors('button_url');
        $this->assertSame(2, collect(HomeSection::where('type', 'steps')->first()->settings['items'])->count());   // empty item dropped
        $this->get('/admin/home-sections/create?type=faq')->assertOk();

        auth()->logout();
        $this->get('/')->assertOk()->assertSee('Easy as 1-2-3')->assertSee('Order it')->assertSee('Do you deliver?')->assertSee('Great service!')->assertSee('Lahore')->assertSee('Our brands')->assertSee('Acme')->assertSee('Join us today');
    }

    public function test_seo_fields_reach_the_page_head(): void
    {
        $cat = Category::create(['name' => 'Tools']);
        $p = $this->product(['name' => 'Hammer', 'slug' => 'hammer', 'category_id' => $cat->id, 'meta_title' => 'Best Hammer in Town', 'meta_description' => 'Hits nails, not thumbs.']);
        $this->get('/product/hammer')->assertSee('<title>Best Hammer in Town', false)->assertSee('Hits nails, not thumbs.');
        $cat->update(['meta_title' => 'Tools & more']);
        $this->get('/category/'.$cat->slug)->assertSee('<title>Tools &amp; more', false);

        $this->actingAs($this->owner)->put("/admin/products/{$p->id}", ['name' => 'Hammer', 'type' => 'simple', 'status' => 'active', 'price' => 100, 'meta_title' => 'New SEO title', 'track_stock' => 1])->assertSessionHasNoErrors();
        $this->assertSame('New SEO title', $p->fresh()->meta_title);
        $this->put("/admin/products/{$p->id}", ['name' => 'Hammer', 'type' => 'simple', 'status' => 'active', 'price' => 100, 'meta_title' => str_repeat('x', 200)])->assertSessionHasErrors('meta_title');
        auth()->logout();
        $this->opts(['seo_title' => ['en' => 'My shop — best prices'], 'seo_description' => ['en' => 'Everything for less']]);
        $this->get('/')->assertSee('My shop — best prices')->assertSee('Everything for less');
    }

    public function test_checkout_fields_and_invoice_follow_the_switches(): void
    {
        $p = $this->product();
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => 1])->assertOk();
        $this->get('/checkout')->assertSee('ckArea', false)->assertSee('ckNotes', false);
        $this->opts(['checkout_show_area' => '0', 'checkout_show_notes' => '0', 'checkout_show_postal' => '1']);
        $this->get('/checkout')->assertDontSee('ckArea', false)->assertDontSee('ckNotes', false)->assertSee('ckPostal', false);
        $this->opts(['invoice_tax_id' => 'NTN 1234567-8', 'invoice_footer' => ['en' => 'No returns without invoice'], 'invoice_show_logo' => '1']);
        $this->assertSame('NTN 1234567-8', sf('invoice.tax_id'));
    }

    public function test_cards_options_apply(): void
    {
        $this->product(['name' => 'Gizmo']);
        $this->opts(['cards_ratio' => '4 / 3', 'cards_fit' => 'cover', 'cards_cols' => '5', 'cards_per_page' => '4', 'cards_show_wish' => '1', 'cards_add_label' => ['en' => 'Grab it']]);
        $this->get('/shop')->assertSee('--mk-ratio:4 / 3', false)->assertSee('--mk-fit:cover', false)->assertSee('row-cols-xl-4', false)->assertSee('Grab it')->assertDontSee('data-compare-toggle', false);
    }
}
