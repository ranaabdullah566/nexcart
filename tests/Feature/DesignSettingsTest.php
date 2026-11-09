<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Design;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('owner')));
    }

    private function form(array $design = [], array $extra = []): array
    {
        return ['primary' => '#8b5cf6', 'accent' => '#22d3ee', 'mode' => 'light', 'radius' => '1rem', 'font' => 'inter', 'design' => $design] + $extra;
    }

    public function test_default_design_adds_no_overrides_and_the_page_renders(): void
    {
        $this->assertFalse(Design::isCustom());
        $this->get('/admin/theme')->assertOk()->assertSee('Marketplace design')->assertSee('data-preset', false);
        $this->get('/')->assertOk()->assertDontSee('id="nx-design"', false);
    }

    public function test_saved_colours_fonts_and_shape_reach_the_storefront_and_the_admin(): void
    {
        $this->put('/admin/theme', $this->form([
            'navy' => '#064e3b', 'orange' => '#0f766e', 'font' => 'serif', 'heading_font' => 'space', 'font_size' => '17', 'radius' => '4', 'button' => 'pill', 'shadow' => 'none', 'width' => '1140',
        ], ['design_apply_admin' => 1]))->assertSessionHasNoErrors();

        $this->assertSame('#064e3b', Design::values()['navy']);
        $store = $this->get('/')->assertOk()->getContent();
        foreach (['--tq-navy:#064e3b', '--tq-orange:#0f766e', 'Georgia', 'font-size:17px', '--tq-r:4px', '--tq-btn-r:999px', 'max-width:1140px'] as $needle) {
            $this->assertStringContainsString($needle, $store);
        }
        $this->get('/admin')->assertOk()->assertSee('html body.nx-tq', false)->assertSee('--tq-navy:#064e3b', false);

        // "use in admin" off: the admin keeps the default palette, the storefront keeps the custom one
        $this->put('/admin/theme', $this->form(['navy' => '#064e3b']))->assertSessionHasNoErrors();
        $this->get('/admin')->assertOk()->assertDontSee('#064e3b', false);
        $this->get('/')->assertSee('--tq-navy:#064e3b', false);
    }

    public function test_only_allowed_values_are_accepted_and_nothing_can_inject_css(): void
    {
        foreach ([['navy' => 'red'], ['navy' => '#12345'], ['orange' => 'url(javascript:1)'], ['font' => 'Comic Sans; } body{display:none'], ['font_size' => '99'], ['radius' => '-5'], ['button' => 'x'], ['width' => '5000'], ['bg' => '#fff;}</style><script>alert(1)</script>']] as $bad) {
            $this->put('/admin/theme', $this->form($bad))->assertSessionHasErrors();
        }
        $this->assertFalse(Design::isCustom());
        $this->assertStringNotContainsString('<script>alert', $this->get('/')->getContent());
    }

    public function test_reset_restores_the_default_look(): void
    {
        $this->put('/admin/theme', $this->form(['navy' => '#064e3b']));
        $this->assertTrue(Design::isCustom());
        $this->put('/admin/theme', $this->form([], ['design_reset' => 1]))->assertSessionHasNoErrors();
        $this->assertFalse(Design::isCustom());
        $this->get('/')->assertDontSee('id="nx-design"', false);
    }

    public function test_contrast_helper_and_staff_without_permission(): void
    {
        $this->assertGreaterThan(20, Design::contrast('#000000', '#ffffff'));
        $this->assertLessThan(1.1, Design::contrast('#ffffff', '#fefefe'));
        $this->post('/logout');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/theme')->assertForbidden();
        $this->put('/admin/theme', $this->form(['navy' => '#064e3b']))->assertForbidden();
    }
}
