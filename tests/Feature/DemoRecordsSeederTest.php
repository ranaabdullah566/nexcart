<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoRecordsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoRecordsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_100_of_each_with_consistent_numbers_and_a_working_login(): void
    {
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        setting()->setMany(['store.tax_rate' => 16, 'store.tax_inclusive' => false]);
        $this->seed(DemoRecordsSeeder::class);

        $this->assertSame(100, Product::count());
        $this->assertSame(100, Order::count());
        $this->assertSame(100, User::doesntHave('roles')->count());
        foreach (Order::with('items')->get() as $o) {
            $this->assertEqualsWithDelta((float) $o->subtotal, $o->items->sum('total'), 0.001);
            $this->assertEqualsWithDelta((float) $o->total, (float) $o->subtotal + (float) $o->shipping_cost + (float) $o->tax, 0.011);
            $this->assertSame(Order::numberFor($o->id), $o->number);
        }
        $this->get('/shop')->assertOk();
        $u = User::doesntHave('roles')->first();
        $this->post('/login', ['email' => $u->email, 'password' => 'Demo-pass-123'])->assertRedirect();
        $this->assertAuthenticatedAs($u);
    }
}
