<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ProductSheetSyncTest extends TestCase
{
    use RefreshDatabase;

    private const HEAD = ['sku', 'name', 'price', 'active', 'maximumSalesQuantity', 'isWeighted', 'baseWeight', 'baseWeightUnit', 'pricePerBaseUnit', 'barcode 1', 'barcode 2', 'category 1'];

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('owner')));
    }

    private function sheet(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'sh').'.xlsx';
        $w = new Writer;
        $w->openToFile($path);
        $w->addRow(Row::fromValues(self::HEAD));
        foreach ($rows as $r) {
            $w->addRow(Row::fromValues($r));
        }
        $w->close();

        return new UploadedFile($path, 'sheet.xlsx', null, null, true);
    }

    private function upload(array $rows, array $opts = ['update_existing' => 1, 'use_active' => 1])
    {
        return $this->post('/admin/product-sync', ['file' => $this->sheet($rows)] + $opts);
    }

    public function test_new_rows_are_added_and_all_data_is_kept(): void
    {
        $this->upload([['A1', 'Panadol', '10.50', true, 6, false, null, null, null, '111', '222', 'Pharmacy'], ['0', 'Zero SKU', '5', false, null, true, 500, 'g', 20, null, null, 'Baby']])
            ->assertRedirect();

        $this->assertSame(2, Product::count());
        $p = Product::where('sku', 'A1')->first();
        $this->assertSame(['111', '222'], $p->barcodes);
        $this->assertSame(6, $p->max_per_order);
        $this->assertSame('active', $p->status);
        $this->assertSame('Pharmacy', $p->category->name);
        $z = Product::where('sku', '0')->first();
        $this->assertSame('draft', $z->status);
        $this->assertTrue($z->is_weighted);
        $this->assertSame('g', $z->base_weight_unit);
    }

    public function test_second_upload_updates_changed_rows_and_skips_identical_ones(): void
    {
        $rows = [['A1', 'Panadol', '10', true, null, false, null, null, null, null, null, 'Pharmacy'], ['B2', 'Brufen', '20', true, null, false, null, null, null, null, null, 'Pharmacy']];
        $this->upload($rows);
        $rows[0][2] = '12';
        $rows[] = ['C3', 'New one', '5', true, null, false, null, null, null, null, null, 'Pharmacy'];
        $this->upload($rows);

        $run = \App\Models\ProductSync::latest('id')->first();
        $this->assertSame([1, 1, 1], [$run->created, $run->updated, $run->unchanged]);
        $this->assertSame(3, Product::count());
        $this->assertEquals(12, Product::where('sku', 'A1')->value('price'));
    }

    public function test_bad_rows_are_skipped_and_reported(): void
    {
        $this->upload([['A1', 'Ok', '10', true, null, false, null, null, null, null, null, 'X'], ['A1', 'Dup', '10', true, null, false, null, null, null, null, null, 'X'], ['', 'No sku', '10', true, null, false, null, null, null, null, null, 'X'], ['Z', 'Bad price', 'abc', true, null, false, null, null, null, null, null, 'X']]);
        $run = \App\Models\ProductSync::first();
        $this->assertSame([1, 3], [$run->created, $run->skipped]);
        $this->assertCount(3, $run->errors);
    }

    public function test_deactivate_missing_hides_products_left_out(): void
    {
        $a = ['A1', 'A', '1', true, null, false, null, null, null, null, null, 'X'];
        $b = ['B2', 'B', '1', true, null, false, null, null, null, null, null, 'X'];
        $this->upload([$a, $b]);
        $this->upload([$a], ['update_existing' => 1, 'use_active' => 1, 'deactivate_missing' => 1]);
        $this->assertSame('draft', Product::where('sku', 'B2')->value('status'));
        $this->assertSame('active', Product::where('sku', 'A1')->value('status'));
    }

    public function test_max_per_order_is_enforced_in_the_cart(): void
    {
        $this->upload([['A1', 'A', '1', true, 2, false, null, null, null, null, null, 'X']]);
        $p = Product::where('sku', 'A1')->first();
        app(CartService::class)->add($p, null, 2);
        $this->expectException(ValidationException::class);
        app(CartService::class)->add($p, null, 1);
    }

    public function test_page_and_export_work_and_staff_without_permission_are_blocked(): void
    {
        $this->get('/admin/product-sync')->assertOk()->assertSee(__('Product sync'));
        $this->get('/admin/product-sync/export')->assertOk();
        $this->actingAs(User::factory()->create());
        $this->get('/admin/product-sync')->assertStatus(403);
    }
}
