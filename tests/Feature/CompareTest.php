<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
    }

    private function product(string $n, string $status = 'active'): Product
    {
        $p = new Product;
        $p->forceFill(['name' => $n, 'price' => 100, 'status' => $status, 'track_stock' => false])->save();

        return $p;
    }

    public function test_toggle_limit_and_page(): void
    {
        $ps = collect(range(1, 5))->map(fn ($i) => $this->product("P{$i}"));
        foreach ($ps->take(4) as $p) {
            $this->postJson("/compare/{$p->id}")->assertOk()->assertJson(['added' => true]);
        }
        $this->postJson("/compare/{$ps[4]->id}")->assertOk()->assertJson(['added' => false, 'full' => true, 'count' => 4]);
        $this->get('/compare')->assertOk()->assertSee('P1')->assertSee('P4')->assertDontSee('P5');

        $this->postJson("/compare/{$ps[0]->id}")->assertJson(['added' => false, 'count' => 3]);   // toggles off
        $this->postJson("/compare/{$ps[4]->id}")->assertJson(['added' => true]);                   // room again
        $this->delete('/compare')->assertRedirect('/compare');
        $this->get('/compare')->assertSee('Nothing to compare yet');
    }

    public function test_inactive_products_cannot_be_compared(): void
    {
        $this->postJson('/compare/'.$this->product('Hidden', 'draft')->id)->assertNotFound();
    }
}
