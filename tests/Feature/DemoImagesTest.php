<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\User;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class DemoImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_pictures_are_added_only_where_missing_and_removed_cleanly(): void
    {
        Storage::fake('public');
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('owner')));

        $c = Category::create(['name' => 'Syrups', 'is_active' => true]);
        $syrup = (new Product)->forceFill(['name' => 'Cough Syrup 100ml', 'price' => 5, 'status' => 'active', 'track_stock' => false, 'category_id' => $c->id]);
        $syrup->save();
        $box = (new Product)->forceFill(['name' => 'Digital Thermometer', 'price' => 5, 'status' => 'active', 'track_stock' => false, 'category_id' => $c->id]);
        $box->save();
        $mine = (new Product)->forceFill(['name' => 'Mine', 'price' => 5, 'status' => 'active', 'track_stock' => false, 'category_id' => $c->id]);
        $mine->save();
        $mine->addMedia(\Illuminate\Http\UploadedFile::fake()->image('own.jpg', 300, 300))->toMediaCollection('images');
        $this->post('/admin/home-sections/demo')->assertRedirect();

        $this->post('/admin/home-sections/images')->assertRedirect()->assertSessionHas('success');
        $this->assertCount(1, $syrup->fresh()->getMedia('images'));
        $this->assertCount(1, $box->fresh()->getMedia('images'));
        $this->assertCount(1, $mine->fresh()->getMedia('images'));   // the uploaded one only: nothing added next to it
        $this->assertCount(1, $c->fresh()->getMedia('image'));
        foreach (HomeSection::whereIn('type', ['hero', 'banner', 'cta'])->get() as $s) {
            $this->assertNotNull($s->imageUrl(), "no picture for {$s->type}");
        }
        $this->get('/')->assertOk();

        // a second click does nothing new
        $this->post('/admin/home-sections/images')->assertSessionHas('error');

        $this->delete('/admin/home-sections/images')->assertRedirect();
        $this->assertCount(0, $syrup->fresh()->getMedia('images'));
        $this->assertCount(1, $mine->fresh()->getMedia('images'));   // the owner's own picture survives
        $this->assertSame(1, Media::count());
    }
}
