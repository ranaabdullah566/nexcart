<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_table_pages_searches_and_sorts_on_the_server(): void
    {
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('owner')));
        foreach (range(1, 30) as $i) {
            (new Product)->forceFill(['name' => 'Item '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'sku' => 'SKU'.$i, 'price' => $i * 10, 'status' => 'active', 'track_stock' => false, 'barcodes' => $i === 7 ? ['8901234'] : null])->save();
        }

        // the page itself carries no rows (it used to print every product)
        $this->get('/admin/products')->assertOk()->assertDontSee('Item 05')->assertSee('data-src', false);

        $j = $this->getJson('/admin/products/data?draw=3&start=0&length=10')->assertOk()->json();
        $this->assertSame([3, 30, 30, 10], [$j['draw'], $j['recordsTotal'], $j['recordsFiltered'], count($j['data'])]);
        $this->assertCount(6, $j['data'][0]);

        $s = $this->getJson('/admin/products/data?draw=1&start=0&length=10&search[value]=8901234')->json();
        $this->assertSame([1, 30], [$s['recordsFiltered'], $s['recordsTotal']]);
        $this->assertStringContainsString('Item 07', $s['data'][0][0]);

        $o = $this->getJson('/admin/products/data?draw=1&start=0&length=5&order[0][column]=2&order[0][dir]=desc')->json();
        $this->assertStringContainsString('300.00', $o['data'][0][2]);

        // staff without the permission get nothing
        $this->actingAs(User::factory()->create());
        $this->getJson('/admin/products/data')->assertForbidden();
    }

    public function test_category_pickers_in_admin_forms_show_the_category_names(): void
    {
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('owner')));
        $c = \App\Models\Category::create(['name' => 'Pickable cat', 'is_active' => true]);
        $p = (new Product)->forceFill(['name' => 'P', 'price' => 1, 'status' => 'active', 'track_stock' => false, 'category_id' => $c->id]);
        $p->save();

        $this->get("/admin/products/{$p->id}/edit")->assertOk()->assertSee('>Pickable cat</option>', false);
        $this->get('/admin/products/create')->assertOk()->assertSee('>Pickable cat</option>', false);
        $this->get('/admin/home-sections/create?type=categories')->assertOk()->assertSee('Pickable cat');
        $this->get('/admin/home-sections/create?type=products')->assertOk()->assertSee('Pickable cat');
    }

    public function test_product_form_saves_barcodes_and_the_order_limit(): void
    {
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('owner')));
        $p = (new Product)->forceFill(['name' => 'Coded', 'price' => 10, 'status' => 'active', 'track_stock' => false]);
        $p->save();

        $this->put("/admin/products/{$p->id}", ['name' => 'Coded', 'type' => 'simple', 'status' => 'active', 'price' => 10, 'max_per_order' => 4, 'barcodes' => "111\n222, 111\n  \n333"])->assertSessionHasNoErrors();
        $p->refresh();
        $this->assertSame(['111', '222', '333'], $p->barcodes);
        $this->assertSame(4, $p->max_per_order);
        $this->get("/admin/products/{$p->id}/edit")->assertOk()->assertSee('333');

        $this->put("/admin/products/{$p->id}", ['name' => 'Coded', 'type' => 'simple', 'status' => 'active', 'price' => 10, 'max_per_order' => 0])->assertSessionHasErrors('max_per_order');
        $this->put("/admin/products/{$p->id}", ['name' => 'Coded', 'type' => 'simple', 'status' => 'active', 'price' => 10, 'max_per_order' => '', 'barcodes' => ''])->assertSessionHasNoErrors();
        $this->assertNull($p->fresh()->barcodes);
        $this->assertNull($p->fresh()->max_per_order);
    }
}
