<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
    }

    private function as(string $role): User
    {
        return tap(User::factory()->create(), fn ($u) => $u->assignRole($role));
    }

    public function test_owner_creates_custom_role_and_it_limits_access(): void
    {
        $this->actingAs($this->as('owner'))->post('/admin/roles', ['name' => 'Cashier', 'permissions' => ['orders.view', 'orders.update']])->assertRedirect('/admin/roles');
        $role = Role::findByName('cashier');
        $this->assertEqualsCanonicalizing(['orders.view', 'orders.update'], $role->permissions->pluck('name')->all());

        $cashier = $this->as('cashier');
        $this->actingAs($cashier)->get('/admin/orders')->assertOk();
        $this->get('/admin/products')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
        $this->get('/admin/activity')->assertForbidden();
        $this->assertTrue($cashier->isStaff());
    }

    public function test_editing_permissions_takes_effect_and_is_audited(): void
    {
        $role = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $owner = $this->as('owner');
        $this->actingAs($owner)->put("/admin/roles/{$role->id}", ['permissions' => ['products.view']])->assertRedirect('/admin/roles');
        $this->actingAs($this->as('viewer'))->get('/admin/products')->assertOk();
        $this->assertDatabaseHas('activity_log', ['event' => 'role.updated']);
    }

    public function test_owner_role_is_locked_and_builtins_cannot_be_deleted(): void
    {
        $owner = $this->as('owner');
        $ownerRole = Role::findByName('owner');
        $this->actingAs($owner)->put("/admin/roles/{$ownerRole->id}", ['permissions' => []])->assertForbidden();
        $this->delete("/admin/roles/{$ownerRole->id}")->assertSessionHas('error');
        $this->delete('/admin/roles/'.Role::findByName('manager')->id)->assertSessionHas('error');
        $this->assertTrue(Role::where('name', 'manager')->exists());
    }

    public function test_role_in_use_cannot_be_deleted_and_manager_cannot_grant_what_it_lacks(): void
    {
        $role = Role::create(['name' => 'temp', 'guard_name' => 'web']);
        $this->as('temp');
        $this->actingAs($this->as('owner'))->delete("/admin/roles/{$role->id}")->assertSessionHas('error');

        $manager = $this->as('manager');
        $this->actingAs($manager)->get('/admin/roles')->assertForbidden();
    }

    public function test_trash_actions_need_their_own_permission(): void
    {
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
        $brand->delete();
        Role::create(['name' => 'limited', 'guard_name' => 'web'])->syncPermissions(['products.view', 'products.delete']);
        $this->actingAs($this->as('limited'))->get('/admin/trash')->assertOk();
        $this->post("/admin/trash/brands/{$brand->id}/restore")->assertForbidden();
        $this->delete("/admin/trash/brands/{$brand->id}")->assertForbidden();
    }

    public function test_updates_keep_customised_roles_and_only_add_new_permissions(): void
    {
        $manager = Role::findByName('manager');
        $manager->revokePermissionTo('products.delete');
        $manager->givePermissionTo('staff.delete');                       // owner chose to allow this
        \Spatie\Permission\Models\Permission::where('name', 'alerts.view')->delete();   // pretend this permission is new in this update
        Role::findByName('staff')->givePermissionTo('products.update');

        (new RolesSeeder)->run();                                          // what `nexcart:update` does

        $m = Role::findByName('manager')->fresh();
        $this->assertFalse($m->hasPermissionTo('products.delete'));         // customisation kept
        $this->assertTrue($m->hasPermissionTo('staff.delete'));
        $this->assertTrue($m->hasPermissionTo('alerts.view'));              // new permission arrived
        $this->assertTrue(Role::findByName('staff')->fresh()->hasPermissionTo('products.update'));
        $this->assertTrue(Role::findByName('owner')->fresh()->hasPermissionTo('alerts.view'));

        $this->artisan('nexcart:sync-permissions', ['--reset-roles' => true])->assertSuccessful();   // deliberate factory reset
        $this->assertTrue(Role::findByName('manager')->fresh()->hasPermissionTo('products.delete'));
        $this->assertFalse(Role::findByName('manager')->fresh()->hasPermissionTo('staff.delete'));
    }
}
