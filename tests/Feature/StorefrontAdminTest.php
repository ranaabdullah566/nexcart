<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Sf;
use App\Support\UiText;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        $this->owner = tap(User::factory()->create(), fn ($u) => $u->assignRole('owner'));
        foreach (['en', 'ur', 'ur_Latn'] as $l) {
            UiText::save($l, []);
        }
    }

    protected function tearDown(): void
    {
        foreach (['en', 'ur', 'ur_Latn'] as $l) {
            UiText::save($l, []);
        }
        parent::tearDown();
    }

    public function test_defaults_come_from_the_schema_and_the_form_saves_everything(): void
    {
        $this->assertTrue(sf('header.show_deals'));
        $this->assertSame(3, sf('home.hero_deals_count'));
        $this->assertSame('Deals', sft('header.deals_label'));

        $this->actingAs($this->owner)->get('/admin/storefront')->assertOk()->assertSee('Deals link text');
        $this->put('/admin/storefront', ['v' => [
            'header_deals_label' => ['en' => 'Offers', 'ur' => 'آفرز'], 'cards_cols' => '5', 'home_hero_deals_count' => '2', 'header_show_deals' => '1',
        ]])->assertRedirect();
        $this->assertSame('Offers', sft('header.deals_label'));
        $this->assertSame('5', sf('cards.cols'));
        $this->assertSame(2, sf('home.hero_deals_count'));
        $this->assertFalse(sf('header.show_shop'));   // unticked switch = off
        $this->withSession(['locale' => 'ur'])->get('/');
        app()->setLocale('ur');
        $this->assertSame('آفرز', sft('header.deals_label'));
        app()->setLocale('ur_Latn');
        $this->assertSame('Offers', sft('header.deals_label'));   // falls back to the owner's English
    }

    public function test_bad_values_are_rejected_and_html_is_stripped(): void
    {
        $this->actingAs($this->owner)->put('/admin/storefront', ['v' => ['cards_cols' => '9']])->assertSessionHasErrors('v.cards_cols');
        $this->put('/admin/storefront', ['v' => ['home_hero_deals_count' => '99']])->assertSessionHasErrors('v.home_hero_deals_count');
        $this->put('/admin/storefront', ['v' => ['topbar_text' => ['en' => '<script>alert(1)</script>Hello', 'xx' => 'ignored']]])->assertSessionHasNoErrors();
        $this->assertSame('alert(1)Hello', Sf::stored('topbar.text', 'en'));
        $this->assertSame('', Sf::stored('topbar.text', 'xx'));
    }

    public function test_lines_parse_and_default_words_are_translated(): void
    {
        $rows = Sf::lines('product.bb_trust');
        $this->assertSame(['patch-check-fill', 'Genuine products'], $rows[0]);
        $this->actingAs($this->owner)->put('/admin/storefront', ['v' => ['product_bb_trust' => ['en' => "award|Original\n\nbad line"]]]);
        $this->assertSame([['award', 'Original'], ['bad line']], Sf::lines('product.bb_trust'));
    }

    public function test_interface_text_overrides_win_in_every_language_and_can_be_reset(): void
    {
        $this->assertSame('Cart', __('Cart'));
        $this->actingAs($this->owner)->put('/admin/texts', ['locale' => 'en', 'keys' => ['Add to cart'], 'vals' => ['Put in basket']])->assertRedirect();
        $this->assertFileExists(UiText::path('en'));
        app('translator')->setLoaded([]);   // a real next request starts with a fresh translator
        $this->get('/')->assertSee('Put in basket');

        $this->put('/admin/texts', ['locale' => 'en', 'keys' => ['Add to cart'], 'vals' => ['']])->assertRedirect();
        $this->assertSame([], UiText::overrides('en'));   // empty = back to built-in

        $this->put('/admin/texts', ['locale' => 'en', 'keys' => ['not a real key'], 'vals' => ['x']]);
        $this->assertSame([], UiText::overrides('en'));   // only real interface strings
        $this->get('/admin/texts?locale=ur&q=Cart')->assertOk()->assertSee('Add to cart');
        $this->put('/admin/texts', ['locale' => 'ur', 'keys' => ['Add to cart'], 'vals' => ['ٹوکری میں ڈالیں']]);
        $this->delete('/admin/texts', ['locale' => 'ur'])->assertRedirect();
        $this->assertSame([], UiText::overrides('ur'));
    }

    public function test_only_people_with_storefront_permission_can_open_these_pages(): void
    {
        $rider = tap(User::factory()->create(), fn ($u) => $u->assignRole('rider'));
        $this->actingAs($rider)->get('/admin/storefront')->assertForbidden();
        $this->get('/admin/texts')->assertForbidden();
        $this->put('/admin/storefront', [])->assertForbidden();
    }
}
