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

    public function test_guests_are_sent_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_customers_without_role_cannot_enter_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_owner_can_open_dashboard_settings_and_staff(): void
    {
        $this->actingAs($this->userWithRole('owner'));
        $this->get('/admin')->assertOk();
        $this->get('/admin/manage-settings')->assertOk();
        $this->get('/admin/users')->assertOk();
    }

    public function test_staff_role_cannot_open_settings_or_staff_list(): void
    {
        $this->actingAs($this->userWithRole('staff'));
        $this->get('/admin')->assertOk();
        $this->get('/admin/manage-settings')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
    }
}
