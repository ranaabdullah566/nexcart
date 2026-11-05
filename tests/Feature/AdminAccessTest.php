<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
    }

    private function userWithRole(string $role): User
    {
        return tap(User::factory()->create(), fn (User $u) => $u->assignRole($role));
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Sign in');
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);
        $user->assignRole('manager');

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass-1'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_customers_without_role_cannot_enter_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_owner_can_open_dashboard_settings_and_staff(): void
    {
        $this->actingAs($this->userWithRole('owner'));
        $this->get('/admin')->assertOk()->assertSee('nx-sidebar', false);
        $this->get('/admin/settings')->assertOk()->assertSee('Store settings');
        $this->get('/admin/staff')->assertOk();
        $this->get('/admin/staff/create')->assertOk();
    }

    public function test_staff_role_cannot_open_settings_or_staff_list(): void
    {
        $this->actingAs($this->userWithRole('staff'));
        $this->get('/admin')->assertOk();
        $this->get('/admin/settings')->assertForbidden();
        $this->get('/admin/staff')->assertForbidden();
    }

    public function test_dashboard_renders_in_every_language(): void
    {
        $this->actingAs($this->userWithRole('owner'));

        foreach (['en', 'ur', 'ur_Latn'] as $locale) {
            $this->withSession(['locale' => $locale])->get('/admin')->assertOk();
        }
        $this->withSession(['locale' => 'ur'])->get('/admin')->assertSee('dir="rtl"', false)->assertSee('ڈیش بورڈ');
    }
}
