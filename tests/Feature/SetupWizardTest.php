<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([

            'store_name' => 'Health Plus',
            'currency' => 'pkr',
            'currency_symbol' => 'Rs',
            'timezone' => 'Asia/Karachi',
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ], $override);
    }

    public function test_visitors_are_redirected_to_setup_until_installed(): void
    {
        $this->get('/')->assertRedirect(route('setup'));
        $this->get('/setup')->assertOk()->assertSee('pharmacy')->assertSee('mk-wz', false)->assertSee('data-strength', false);
    }

    public function test_setup_creates_owner_applies_profile_and_locks_wizard(): void
    {
        $this->post('/setup', $this->payload())->assertRedirect('/admin');

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $this->assertTrue($owner->hasRole('owner'));
        $this->assertTrue($owner->can('settings.update'));

        $this->assertTrue(setting()->isInstalled());
        $this->assertSame('Health Plus', setting('store.name'));
        $this->assertSame('PKR', setting('store.currency'));
        $this->assertSame('pharmacy', setting('business.type'));
        $this->assertTrue(BusinessProfile::hasModule('prescriptions'));
        $this->assertTrue(BusinessProfile::hasModule('riders'));

        $this->get('/setup')->assertRedirect('/');
    }

    public function test_setup_validates_input(): void
    {
        $this->post('/setup', $this->payload(['password_confirmation' => 'x']))
            ->assertSessionHasErrors(['password']);

        $this->assertFalse(setting()->isInstalled());
    }

    public function test_admin_overrides_win_over_profile_modules(): void
    {
        $this->post('/setup', $this->payload());
        $this->assertTrue(BusinessProfile::hasModule('riders'));

        setting()->set('modules.riders', false);
        $this->assertFalse(BusinessProfile::hasModule('riders'));
    }
}
