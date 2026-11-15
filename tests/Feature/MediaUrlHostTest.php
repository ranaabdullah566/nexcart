<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MediaUrlHostTest extends TestCase
{
    use RefreshDatabase;

    public function test_picture_links_use_the_host_the_visitor_is_on(): void
    {
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('owner')));
        $p = (new Product)->forceFill(['name' => 'Regasta', 'price' => 5, 'status' => 'active', 'track_stock' => false]);
        $p->save();
        $p->addMedia(UploadedFile::fake()->image('r.jpg', 300, 300))->toMediaCollection('images');

        try {
            $html = $this->call('GET', "http://127.0.0.1:8000/admin/products/{$p->id}/edit")->assertOk()->getContent();
            $this->assertStringContainsString('src="http://127.0.0.1:8000/storage/', $html);
            $this->assertStringNotContainsString('src="http://localhost:8000/storage/', $html);
        } finally {
            $p->forceDelete();   // removes the real files this test wrote
        }
    }
}
