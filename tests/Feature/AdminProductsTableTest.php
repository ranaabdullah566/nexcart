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
}
