<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\User;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('owner')));
        $c = Category::create(['name' => 'Pain relief', 'is_active' => true]);
        (new Product)->forceFill(['name' => 'Panadol', 'price' => 5, 'status' => 'active', 'track_stock' => false, 'category_id' => $c->id, 'is_featured' => true])->save();
    }

    public function test_sample_sections_cover_every_type_are_live_and_can_be_removed(): void
    {
        $before = HomeSection::count();
        $this->post('/admin/home-sections/demo')->assertRedirect();

        $demo = HomeSection::where('settings->demo', true)->get();
        foreach (HomeSection::TYPES as $type) {
            $this->assertTrue($demo->contains('type', $type), "missing sample $type");
        }
        $this->assertTrue($demo->every->is_active);

        $this->get('/')->assertOk()->assertSee('Sample hero')->assertSee('Sample: how ordering works')->assertSee('Sample: questions people ask')->assertSee('Sample: about our pharmacy')
            ->assertSee('Sample: up to 20% off vitamins')->assertSee('Sample: what customers say')->assertSee('Sample: featured picks');
        $this->get('/admin/home-sections')->assertOk()->assertSee('Remove sample sections');

        // a second click does nothing new
        $this->post('/admin/home-sections/demo')->assertSessionHas('error');
        $this->assertSame($demo->count() + $before, HomeSection::count());

        $this->delete('/admin/home-sections/demo')->assertRedirect();
        $this->assertSame($before, HomeSection::count());
        $this->get('/')->assertOk()->assertDontSee('Sample hero');
    }
}
