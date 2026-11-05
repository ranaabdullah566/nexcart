<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Coupon;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrashTest extends TestCase
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

    public function test_delete_restore_and_erase_cycle(): void
    {
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
        $this->actingAs($this->owner)->delete("/admin/brands/{$brand->id}")->assertRedirect();
        $this->assertSoftDeleted('brands', ['id' => $brand->id]);

        $this->get('/admin/trash?type=brands')->assertOk()->assertSee('Acme');

        $this->post("/admin/trash/brands/{$brand->id}/restore")->assertRedirect();
        $this->assertNotSoftDeleted('brands', ['id' => $brand->id]);

        $brand->delete();
        $this->delete("/admin/trash/brands/{$brand->id}")->assertRedirect();
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        $this->assertDatabaseHas('activity_log', ['event' => 'trash.erased']);
    }

    public function test_empty_bin_and_permissions(): void
    {
        $c = Coupon::create(['code' => 'X1', 'type' => 'percent', 'value' => 5, 'is_active' => true]);
        $c->delete();
        $this->actingAs($this->owner)->delete('/admin/trash/coupons')->assertRedirect();
        $this->assertDatabaseMissing('coupons', ['id' => $c->id]);

        $this->get('/admin/trash?type=nope')->assertNotFound();

        $this->post('/logout');
        $cashier = tap(User::factory()->create(), fn ($u) => $u->assignRole('rider'));
        $this->actingAs($cashier)->get('/admin/trash')->assertStatus(403);
    }

    public function test_purge_command_removes_old_items(): void
    {
        $b = Brand::create(['name' => 'Old', 'slug' => 'old']);
        $b->delete();
        \DB::table('brands')->where('id', $b->id)->update(['deleted_at' => now()->subDays(40)]);
        $this->artisan('nexcart:trash:purge', ['--days' => 30])->assertSuccessful();
        $this->assertDatabaseMissing('brands', ['id' => $b->id]);
    }

    public function test_log_viewer_is_owner_only(): void
    {
        $this->get('/admin/log-viewer')->assertRedirect();
        $this->actingAs($this->owner)->get('/admin/log-viewer')->assertOk();
        $this->get('/admin/activity')->assertOk();
    }
}
