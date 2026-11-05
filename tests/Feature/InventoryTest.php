<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('owner')));
    }

    private function product(array $o = []): Product
    {
        return Product::create($o + ['name' => 'Item', 'price' => 10, 'status' => 'active', 'stock_qty' => 10, 'track_stock' => true]);
    }

    public function test_adjust_adds_and_removes_stock_with_audit_trail(): void
    {
        BusinessProfile::apply('general');
        $p = $this->product();

        $this->post('/admin/inventory/adjust', ['item' => "p:{$p->id}", 'direction' => 'in', 'qty' => 5, 'reason' => 'received'])->assertRedirect('/admin/inventory');
        $this->post('/admin/inventory/adjust', ['item' => "p:{$p->id}", 'direction' => 'out', 'qty' => 3, 'reason' => 'damaged', 'note' => 'dropped'])->assertRedirect();

        $this->assertSame(12, $p->fresh()->stock_qty);
        $m = StockMovement::latest('id')->first();
        $this->assertSame([-3, 12, 'damaged', 'dropped'], [$m->change, $m->balance, $m->reason, $m->note]);
        $this->assertNotNull($m->user_id);
    }

    public function test_cannot_remove_more_than_available(): void
    {
        BusinessProfile::apply('general');
        $p = $this->product(['stock_qty' => 2]);

        $this->post('/admin/inventory/adjust', ['item' => "p:{$p->id}", 'direction' => 'out', 'qty' => 3, 'reason' => 'adjustment'])->assertSessionHasErrors('change');
        $this->assertSame(2, $p->fresh()->stock_qty);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_variant_stock_is_adjusted_separately(): void
    {
        BusinessProfile::apply('general');
        $p = $this->product(['type' => 'variable', 'stock_qty' => 0]);
        $v = $p->variants()->create(['name' => 'L', 'price' => 10, 'stock_qty' => 4]);

        $this->post('/admin/inventory/adjust', ['item' => "v:{$v->id}", 'direction' => 'in', 'qty' => 6, 'reason' => 'received'])->assertRedirect();
        $this->assertSame(10, $v->fresh()->stock_qty);
        $this->assertSame(10, $p->fresh()->load('variants')->totalStock());
        $this->assertDatabaseHas('stock_movements', ['variant_id' => $v->id, 'change' => 6]);
    }

    public function test_untracked_products_reject_adjustments(): void
    {
        $p = $this->product(['track_stock' => false]);

        $this->expectException(ValidationException::class);
        app(StockService::class)->adjust($p, 1, 'received');
    }

    public function test_batch_module_requires_batch_number_and_uses_fefo(): void
    {
        BusinessProfile::apply('pharmacy');
        $p = $this->product(['stock_qty' => 0]);
        $svc = app(StockService::class);

        $this->post('/admin/inventory/adjust', ['item' => "p:{$p->id}", 'direction' => 'in', 'qty' => 5, 'reason' => 'received'])->assertSessionHasErrors('batch_no');

        $svc->adjust($p, 10, 'received', null, ['batch_no' => 'LATE', 'expires_at' => now()->addYear()->toDateString()]);
        $svc->adjust($p, 4, 'received', null, ['batch_no' => 'SOON', 'expires_at' => now()->addMonth()->toDateString()]);
        $svc->adjust($p, 2, 'received', null, ['batch_no' => 'SOON']);   // same batch tops up
        $this->assertSame(2, ProductBatch::count());
        $this->assertSame(16, $p->fresh()->stock_qty);

        // sell 8: all 6 of SOON first, then 2 of LATE
        $svc->adjust($p, -8, 'sold');
        $this->assertSame(0, ProductBatch::firstWhere('batch_no', 'SOON')->qty);
        $this->assertSame(8, ProductBatch::firstWhere('batch_no', 'LATE')->qty);
        $this->assertSame(8, $p->fresh()->stock_qty);
        $this->assertSame(2, StockMovement::where('reason', 'sold')->count());
    }

    public function test_stock_from_before_batches_is_still_sellable(): void
    {
        BusinessProfile::apply('pharmacy');
        $p = $this->product(['stock_qty' => 5]);   // opening stock, no batch
        app(StockService::class)->adjust($p, -3, 'sold');

        $this->assertSame(2, $p->fresh()->stock_qty);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $p->id, 'change' => -3, 'batch_id' => null]);
    }

    public function test_inventory_page_lists_low_stock_and_expiring_batches(): void
    {
        BusinessProfile::apply('pharmacy');
        $low = $this->product(['name' => 'Almost Gone', 'stock_qty' => 1, 'low_stock_threshold' => 5]);
        app(StockService::class)->adjust($low, 3, 'received', null, ['batch_no' => 'B-7', 'expires_at' => now()->addDays(10)->toDateString()]);

        $this->get('/admin/inventory')->assertOk()->assertSee('Almost Gone')->assertSee('B-7')->assertSee('days left');
        $this->get('/admin')->assertOk()->assertSee('Almost Gone');
    }

    public function test_batches_tab_hidden_when_module_off(): void
    {
        BusinessProfile::apply('general');
        $this->get('/admin/inventory')->assertOk()->assertDontSee('Batches &amp; expiry', false);
        $this->get('/admin/inventory/adjust')->assertOk()->assertDontSee('Batch number');
    }
}
