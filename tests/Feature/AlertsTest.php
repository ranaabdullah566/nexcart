<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertsTest extends TestCase
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

    public function test_notify_messages_land_in_the_alerts_page_and_header_has_visit_site(): void
    {
        $owner = $this->as('owner');
        $this->actingAs($owner)->post('/admin/brands', ['name' => 'Acme'])->assertRedirect();
        $this->assertDatabaseHas('admin_alerts', ['user_id' => $owner->id, 'type' => 'success', 'source' => 'notify']);
        $this->get('/admin/alerts')->assertOk()->assertSee('Brand')->assertSee('Visit site');
    }

    public function test_new_order_and_low_stock_alert_staff_and_read_state_is_per_person(): void
    {
        $owner = $this->as('owner');
        $rider = $this->as('rider');
        $p = new Product;
        $p->forceFill(['name' => 'Rice', 'price' => 10, 'status' => 'active', 'track_stock' => true, 'stock_qty' => 7, 'low_stock_threshold' => 5])->save();
        app(StockService::class)->adjust($p->fresh(), -3, 'sold');
        $this->assertDatabaseHas('admin_alerts', ['user_id' => $owner->id, 'source' => 'stock']);
        $this->assertDatabaseMissing('admin_alerts', ['user_id' => $rider->id, 'source' => 'stock']);   // riders hold no inventory permission

        $a = Alert::where('user_id', $owner->id)->first();
        $this->actingAs($rider)->post("/admin/alerts/{$a->id}/read")->assertNotFound();   // not theirs
        $this->actingAs($owner)->post("/admin/alerts/{$a->id}/read")->assertRedirect();
        $this->assertNotNull($a->fresh()->read_at);
        $this->post('/admin/alerts/read-all')->assertRedirect();
        $this->delete('/admin/alerts')->assertRedirect();
        $this->assertSame(0, Alert::where('user_id', $owner->id)->where('source', 'stock')->count());
    }
}
