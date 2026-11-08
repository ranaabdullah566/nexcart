<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        $this->owner = tap(User::factory()->create(), fn ($u) => $u->assignRole('owner'));
        $this->actingAs($this->owner);
    }

    public function test_all_catalog_pages_render(): void
    {
        BusinessProfile::apply('pharmacy');
        $cat = Category::create(['name' => 'Burgers']);
        $brand = Brand::create(['name' => 'Hub']);
        $attr = CatalogAttribute::first();
        $product = Product::create(['name' => 'Zinger', 'price' => 500, 'status' => 'active', 'category_id' => $cat->id]);

        foreach (['', '/products', '/products/create', "/products/{$product->id}/edit", '/categories', '/categories/create', "/categories/{$cat->id}/edit",
            '/brands', '/brands/create', "/brands/{$brand->id}/edit", '/attributes', '/attributes/create', "/attributes/{$attr->id}/edit",
            '/inventory', '/inventory/adjust'] as $path) {
            $this->get('/admin'.$path)->assertOk();
        }
    }

    public function test_category_tree_and_loop_protection(): void
    {
        $this->post('/admin/categories', ['name' => 'Drinks', 'is_active' => 1])->assertRedirect('/admin/categories');
        $drinks = Category::firstWhere('name', 'Drinks');
        $this->post('/admin/categories', ['name' => 'Cold', 'parent_id' => $drinks->id])->assertRedirect();
        $cold = Category::firstWhere('name', 'Cold');

        $this->assertSame('Drinks › Cold', $cold->path);
        $this->assertSame([$drinks->id, $cold->id], $drinks->descendantIds());

        // a category cannot become its own child
        $this->put("/admin/categories/{$drinks->id}", ['name' => 'Drinks', 'parent_id' => $cold->id])->assertSessionHasErrors('parent_id');
        $this->put("/admin/categories/{$drinks->id}", ['name' => 'Drinks', 'parent_id' => $drinks->id])->assertSessionHasErrors('parent_id');

        $this->delete("/admin/categories/{$drinks->id}")->assertRedirect();
        $this->assertSoftDeleted($drinks);
        $this->assertSame('Cold', $cold->fresh()->path);   // the child simply shows without its deleted parent
    }

    public function test_translations_and_urdu_slugs(): void
    {
        $this->post('/admin/categories', [
            'name' => 'Medicines', 'tr' => ['ur' => ['name' => 'ادویات'], 'ur_Latn' => ['name' => 'Adwiya'], 'xx' => ['name' => 'bad'], 'en' => ['name' => 'ignored']],
        ]);
        $c = Category::firstWhere('name', 'Medicines');

        $this->assertSame(['ur' => ['name' => 'ادویات'], 'ur_Latn' => ['name' => 'Adwiya']], $c->translations);
        $this->assertSame('ادویات', $c->trans('name', 'ur'));
        $this->assertSame('Medicines', $c->trans('name', 'en'));
        $this->assertSame('Medicines', $c->trans('description') ?? 'Medicines');

        $this->post('/admin/brands', ['name' => 'دوا ساز']);
        $this->assertNotEmpty(Brand::first()->slug);   // Urdu-only name still gets a slug
    }

    public function test_simple_product_with_opening_stock_logs_a_movement(): void
    {
        $this->post('/admin/products', [
            'name' => 'Tee', 'sku' => 'TEE-1', 'type' => 'simple', 'status' => 'active', 'price' => '999.50',
            'track_stock' => 1, 'stock_qty' => 12, 'low_stock_threshold' => 3,
        ])->assertRedirect();

        $p = Product::firstWhere('sku', 'TEE-1');
        $this->assertSame(12, $p->stock_qty);
        $this->assertSame('999.50', $p->price);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $p->id, 'change' => 12, 'balance' => 12, 'reason' => 'received']);

        // stock cannot be edited from the product form after creation
        $this->put("/admin/products/{$p->id}", ['name' => 'Tee v2', 'sku' => 'TEE-1', 'type' => 'simple', 'status' => 'active', 'price' => '1000', 'track_stock' => 1, 'stock_qty' => 999])->assertRedirect();
        $this->assertSame(12, $p->fresh()->stock_qty);
        $this->assertSame('Tee v2', $p->fresh()->name);
    }

    public function test_variable_product_variants_are_synced(): void
    {
        $this->post('/admin/products', [
            'name' => 'Hoodie', 'type' => 'variable', 'status' => 'active', 'track_stock' => 1,
            'variants' => [
                ['name' => 'S', 'sku' => 'H-S', 'price' => 2000, 'stock_qty' => 5, 'is_active' => 1],
                ['name' => 'L', 'sku' => 'H-L', 'price' => 2200, 'stock_qty' => 0, 'is_active' => 1],
            ],
        ])->assertRedirect();

        $p = Product::firstWhere('name', 'Hoodie')->load('variants');
        $this->assertCount(2, $p->variants);
        $this->assertSame('2000.00', $p->displayPrice());
        $this->assertSame(5, $p->totalStock());

        $s = $p->variants[0];
        // remove "L", edit "S" price; stock stays put
        $this->put("/admin/products/{$p->id}", [
            'name' => 'Hoodie', 'type' => 'variable', 'status' => 'active', 'track_stock' => 1,
            'variants' => [['id' => $s->id, 'name' => 'Small', 'sku' => 'H-S', 'price' => 1800, 'stock_qty' => 77, 'is_active' => 1]],
        ])->assertRedirect();

        $p->refresh()->load('variants');
        $this->assertCount(1, $p->variants);
        $this->assertSame('Small', $p->variants[0]->name);
        $this->assertSame(5, $p->variants[0]->stock_qty);
        $this->assertSame('1800.00', $p->price);
    }

    public function test_variable_product_requires_a_variant_and_unique_skus(): void
    {
        $this->post('/admin/products', ['name' => 'X', 'type' => 'variable', 'status' => 'draft'])->assertSessionHasErrors('variants');
        $this->post('/admin/products', [
            'name' => 'X', 'type' => 'variable', 'status' => 'draft',
            'variants' => [['name' => 'A', 'sku' => 'DUP', 'price' => 1], ['name' => 'B', 'sku' => 'DUP', 'price' => 1]],
        ])->assertSessionHasErrors('variants.0.sku');
    }

    public function test_dynamic_attributes_are_validated_and_stored(): void
    {
        BusinessProfile::apply('pharmacy');
        $base = ['name' => 'Panadol', 'type' => 'simple', 'status' => 'active', 'price' => 50, 'track_stock' => 1, 'stock_qty' => 0];

        $this->post('/admin/products', $base + ['specs' => ['composition' => 'Paracetamol', 'dosage_form' => 'Nonsense']])->assertSessionHasErrors('specs.dosage_form');

        $this->post('/admin/products', $base + ['specs' => ['composition' => 'Paracetamol 500mg', 'dosage_form' => 'Tablet'], 'requires_prescription' => 1])->assertRedirect();
        $p = Product::firstWhere('name', 'Panadol');
        $this->assertSame('Paracetamol 500mg', $p->specs['composition']);
        $this->assertTrue($p->requires_prescription);
    }

    public function test_attribute_crud_and_select_needs_options(): void
    {
        $this->post('/admin/attributes', ['label' => 'Material', 'key' => 'material', 'type' => 'select', 'options' => 'Cotton'])->assertSessionHasErrors('options');
        $this->post('/admin/attributes', ['label' => 'Bad Key', 'key' => 'Bad Key', 'type' => 'text'])->assertSessionHasErrors('key');
        $this->post('/admin/attributes', ['label' => 'Material', 'key' => 'material', 'type' => 'select', 'options' => "Cotton\nWool\nCotton"])->assertRedirect();

        $a = CatalogAttribute::firstWhere('key', 'material');
        $this->assertSame(['Cotton', 'Wool'], $a->options);
    }

    public function test_images_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');
        config(['media-library.disk_name' => 'public']);

        $this->post('/admin/products', [
            'name' => 'Pic', 'type' => 'simple', 'status' => 'draft', 'price' => 10,
            'images' => [UploadedFile::fake()->image('a.jpg', 300, 300)],
        ])->assertRedirect();

        $p = Product::firstWhere('name', 'Pic');
        $this->assertCount(1, $p->getMedia('images'));

        $this->post('/admin/products', ['name' => 'Bad', 'type' => 'simple', 'status' => 'draft', 'price' => 10, 'images' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('images.0');

        $this->delete("/admin/products/{$p->id}/images/{$p->getFirstMedia('images')->id}")->assertRedirect();
        $this->assertCount(0, $p->fresh()->getMedia('images'));
    }

    public function test_product_soft_delete_and_unique_slug(): void
    {
        $this->post('/admin/products', ['name' => 'Same', 'type' => 'simple', 'status' => 'draft', 'price' => 1]);
        $this->post('/admin/products', ['name' => 'Same', 'type' => 'simple', 'status' => 'draft', 'price' => 1]);
        $slugs = Product::pluck('slug')->all();
        $this->assertCount(2, array_unique($slugs));

        $p = Product::first();
        $this->delete("/admin/products/{$p->id}")->assertRedirect('/admin/products');
        $this->assertSoftDeleted($p);
    }

    public function test_csv_export_and_import(): void
    {
        Product::create(['name' => '=cmd|calc', 'sku' => 'A1', 'price' => 10, 'status' => 'active', 'stock_qty' => 3]);

        $csv = $this->get('/admin/products/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('sku,name,category', $csv);
        $this->assertStringContainsString("'=cmd|calc", $csv);   // formula injection neutralised

        $file = UploadedFile::fake()->createWithContent('p.csv', "sku,name,category,brand,price,stock_qty,status\nA1,Renamed,Snacks,Acme,25,99,active\nB2,Fresh,Snacks,,5,4,\n,,,,,,\nC3,BadPrice,,,abc,1,\n");
        $this->post('/admin/products/import', ['file' => $file])->assertSessionHas('success');

        $a1 = Product::firstWhere('sku', 'A1');
        $this->assertSame('Renamed', $a1->name);
        $this->assertSame(3, $a1->stock_qty);   // existing stock is never overwritten by import
        $this->assertSame('Snacks', $a1->category->name);
        $b2 = Product::firstWhere('sku', 'B2');
        $this->assertSame(4, $b2->stock_qty);
        $this->assertSame('draft', $b2->status);
        $this->assertNull(Product::firstWhere('sku', 'C3'));
        $this->assertSame(1, Category::where('name', 'Snacks')->count());
    }

    public function test_staff_role_can_view_but_not_change_catalog(): void
    {
        $staff = tap(User::factory()->create(), fn ($u) => $u->assignRole('staff'));
        $this->actingAs($staff);

        $this->get('/admin/products')->assertOk();
        $this->get('/admin/products/create')->assertForbidden();
        $this->post('/admin/products', ['name' => 'X', 'type' => 'simple', 'status' => 'draft', 'price' => 1])->assertForbidden();
        $this->get('/admin/attributes')->assertForbidden();
        $this->get('/admin/inventory')->assertOk();
        $this->get('/admin/inventory/adjust')->assertForbidden();
        $this->assertSame(0, Product::count());
    }
}
