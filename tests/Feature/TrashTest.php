<?php

namespace Tests\Feature;

use App\Models\Category;
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
        $brand = Category::create(['name' => 'Acme']);
        $this->actingAs($this->owner)->delete("/admin/categories/{$brand->id}")->assertRedirect();
        $this->assertSoftDeleted('categories', ['id' => $brand->id]);

        $this->get('/admin/trash?type=categories')->assertOk()->assertSee('Acme');

        $this->post("/admin/trash/categories/{$brand->id}/restore")->assertRedirect();
        $this->assertNotSoftDeleted('categories', ['id' => $brand->id]);

        $brand->delete();
        $this->delete("/admin/trash/categories/{$brand->id}")->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $brand->id]);
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
        $b = Category::create(['name' => 'Old']);
        $b->delete();
        \DB::table('categories')->where('id', $b->id)->update(['deleted_at' => now()->subDays(40)]);
        $this->artisan('nexcart:trash:purge', ['--days' => 30])->assertSuccessful();
        $this->assertDatabaseMissing('categories', ['id' => $b->id]);
    }

    public function test_log_viewer_is_owner_only(): void
    {
        $this->get('/admin/log-viewer')->assertRedirect();
        $this->actingAs($this->owner)->get('/admin/log-viewer')->assertOk();
        $this->get('/admin/activity')->assertOk();
    }
}
