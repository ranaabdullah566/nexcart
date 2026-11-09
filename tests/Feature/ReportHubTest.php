<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportHubTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        $this->owner = tap(User::factory()->create(), fn ($u) => $u->assignRole('owner'));
        $p = Product::create(['name' => 'Widget', 'slug' => 'widget', 'type' => 'simple', 'status' => 'active', 'price' => 100, 'cost' => 60, 'stock_qty' => 10]);
        foreach ([['A1', 'completed', 230, 30, 14, 100], ['A2', 'cancelled', 115, 15, 0, 100], ['A3', 'pending', 345, 45, 0, 300]] as [$n, $st, $total, $tax, $ship, $sub]) {
            $o = Order::forceCreate(['number' => $n, 'public_token' => str()->random(20), 'status' => $st, 'payment_status' => 'unpaid', 'payment_method' => 'cod', 'name' => 'Ali', 'email' => 'a@x.test', 'phone' => '1', 'shipping_method' => 'Standard', 'city' => 'Lahore', 'subtotal' => $sub, 'discount' => 0, 'shipping_cost' => $ship, 'tax' => $tax, 'total' => $total, 'tax_rate' => 15, 'currency' => 'PKR', 'placed_at' => now()]);
            OrderItem::forceCreate(['order_id' => $o->id, 'product_id' => $p->id, 'name' => 'Widget', 'unit_price' => 100, 'qty' => $sub / 100, 'total' => $sub]);
        }
    }

    public function test_every_report_renders_and_numbers_exclude_cancelled(): void
    {
        $this->actingAs($this->owner);
        foreach (array_keys(\App\Http\Controllers\Admin\ReportHubController::TYPES) as $t) {
            $this->get("/admin/reports/{$t}")->assertOk();
            $this->get("/admin/reports/{$t}?range=custom&from=2020-01-01&to=2020-01-31")->assertOk();
        }
        // live orders = A1 (230) + A3 (345) = 575 ; units 1 + 3 = 4 ; profit (100+300) - 60*4 = 160 ; tax 30+45
        $this->get('/admin/reports/sales')->assertSee('575.00')->assertSee('287.50');
        $this->get('/admin/reports/products')->assertSee('160.00');
        $this->get('/admin/reports/finance')->assertSee('75.00');
    }

    public function test_csv_export_and_permissions(): void
    {
        $this->actingAs($this->owner)->get('/admin/reports/sales?export=daily')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->get('/admin/reports/sales?export=nope')->assertNotFound();
        $this->get('/admin/reports/bogus')->assertNotFound();

        $rider = tap(User::factory()->create(), fn ($u) => $u->assignRole('rider'));
        $this->actingAs($rider)->get('/admin/reports/sales')->assertForbidden();
    }

    public function test_period_presets_filters_print_and_export_all(): void
    {
        $this->actingAs($this->owner);
        foreach (['today', 'yesterday', '7', '30', '90', 'month', 'last_month', 'year', '365'] as $r) {
            $this->get("/admin/reports/sales?range={$r}")->assertOk();
            $this->get("/admin/reports?range={$r}")->assertOk();
        }
        $this->get('/admin/reports/sales?range=bogus')->assertSessionHasErrors('range');

        // filters narrow every order-based number (the seeded orders are all cash on delivery in Lahore)
        $this->get('/admin/reports/sales?method=cod&city=Lahore')->assertOk()->assertSee('575.00');
        $this->get('/admin/reports/sales?method=stripe')->assertOk()->assertDontSee('575.00');
        $this->get('/admin/reports/sales?city=Karachi')->assertOk()->assertDontSee('575.00');

        $this->get('/admin/reports/sales')->assertSee('window.print()', false)->assertSee('Export all (CSV)')->assertSee('rp-kpis', false);
        $csv = $this->get('/admin/reports/sales?export=all')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')->streamedContent();
        $this->assertStringContainsString('# Daily sales', $csv);
        $this->assertStringContainsString('KPI,Revenue,575', $csv);
    }

    public function test_pharmacy_report_counts_prescriptions_and_requests(): void
    {
        $rx = Product::create(['name' => 'Amoxil', 'slug' => 'amoxil', 'type' => 'simple', 'status' => 'active', 'price' => 200, 'stock_qty' => 5, 'requires_prescription' => true]);
        $o = Order::forceCreate(['number' => 'R1', 'public_token' => str()->random(20), 'status' => 'completed', 'payment_status' => 'paid', 'payment_method' => 'cod', 'name' => 'Sara', 'email' => 's@x.test', 'phone' => '1', 'shipping_method' => 'Standard', 'city' => 'Lahore', 'subtotal' => 400, 'total' => 400, 'currency' => 'PKR', 'requires_prescription' => true, 'prescription_status' => 'approved', 'prescription_reviewed_by' => $this->owner->id, 'prescription_reviewed_at' => now()->addMinutes(20), 'placed_at' => now()]);
        OrderItem::forceCreate(['order_id' => $o->id, 'product_id' => $rx->id, 'name' => 'Amoxil', 'unit_price' => 200, 'qty' => 2, 'total' => 400]);
        \App\Models\PrescriptionRequest::create(['name' => 'Ali', 'phone' => '0300', 'status' => 'new']);

        $this->actingAs($this->owner)->get('/admin/reports/pharmacy')->assertOk()->assertSee('Prescription orders')->assertSee('Amoxil')->assertSee('Reviews by pharmacist')->assertSee('Call-back requests');
        $csv = $this->get('/admin/reports/pharmacy?export=top')->assertOk()->streamedContent();
        $this->assertStringContainsString('Amoxil,2,400', $csv);

        // a role without the permission is turned away
        $staff = User::factory()->create();
        $this->actingAs($staff)->get('/admin/reports/pharmacy')->assertForbidden();
    }
}
