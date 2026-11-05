<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        $this->owner = tap(User::factory()->create(), fn ($u) => $u->assignRole('owner'));
    }

    public function test_owner_can_update_store_settings(): void
    {
        $this->actingAs($this->owner)->put('/admin/settings', [
            'store' => [
                'name' => 'Burger Hub', 'currency' => 'pkr', 'currency_symbol' => 'Rs', 'timezone' => 'Asia/Karachi',
                'tax_rate' => '16', 'tax_inclusive' => '1', 'default_locale' => 'ur', 'locales' => ['en'],
            ],
            'theme' => ['primary' => '#ff5500', 'accent' => '#00ffaa', 'mode' => 'light'],
            'modules' => ['kitchen_display' => '1', 'prescriptions' => '0'],
        ])->assertRedirect('/admin/settings');

        $this->assertSame('Burger Hub', setting('store.name'));
        $this->assertSame('PKR', setting('store.currency'));
        $this->assertEquals(16, setting('store.tax_rate'));
        $this->assertSame(['en', 'ur'], setting('store.locales'));   // default language is always enabled
        $this->assertSame('#ff5500', setting('theme.primary'));
        $this->assertTrue(setting('modules.kitchen_display'));
        $this->assertFalse(setting('modules.prescriptions'));
    }

    public function test_settings_validation_rejects_bad_colours(): void
    {
        $this->actingAs($this->owner)->put('/admin/settings', [
            'store' => ['name' => 'X', 'currency' => 'PKR', 'currency_symbol' => 'Rs', 'timezone' => 'Asia/Karachi', 'default_locale' => 'en', 'locales' => ['en']],
            'theme' => ['primary' => 'red', 'accent' => '#00ffaa', 'mode' => 'dark'],
        ])->assertSessionHasErrors('theme.primary');
    }

    public function test_staff_crud(): void
    {
        $this->actingAs($this->owner);

        $this->post('/admin/staff', [
            'name' => 'Sara', 'email' => 'sara@example.com', 'role' => 'manager',
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect('/admin/staff');

        $sara = User::where('email', 'sara@example.com')->firstOrFail();
        $this->assertTrue($sara->hasRole('manager'));

        $this->put("/admin/staff/{$sara->id}", ['name' => 'Sara K', 'email' => 'sara@example.com', 'role' => 'rider'])
            ->assertRedirect('/admin/staff');
        $this->assertTrue($sara->fresh()->hasRole('rider'));
        $this->assertFalse($sara->fresh()->hasRole('manager'));

        $this->delete("/admin/staff/{$sara->id}")->assertRedirect('/admin/staff');
        $this->assertModelMissing($sara);
    }

    public function test_cannot_delete_self_or_last_owner_and_manager_cannot_make_owners(): void
    {
        $this->actingAs($this->owner)->delete("/admin/staff/{$this->owner->id}")->assertSessionHas('error');
        $this->assertModelExists($this->owner);

        $manager = tap(User::factory()->create(), fn ($u) => $u->assignRole('manager'));
        $this->actingAs($manager)->post('/admin/staff', [
            'name' => 'Eve', 'email' => 'eve@example.com', 'role' => 'owner',
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'eve@example.com']);
    }
}
