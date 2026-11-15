<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\PrescriptionRequest;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\DemoData;
use App\Support\BusinessProfile;
use App\Support\Storefront;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PharmacyUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        Storage::fake('local');
        Storage::fake('public');   // the demo-data test generates and deletes images: never touch the real disk
        config(['media-library.disk_name' => 'public']);
    }

    private function user(string $role): User
    {
        return tap(User::factory()->create(), fn ($u) => $u->assignRole($role));
    }

    private function med(array $o = []): Product
    {
        $p = new Product;
        $p->forceFill($o + ['name' => 'Med', 'price' => 50, 'status' => 'active', 'track_stock' => false])->save();

        return $p;
    }

    private function payload(array $o = []): array
    {
        return $o + ['name' => 'Sara', 'phone' => '0300 1234567', 'city' => 'Lahore', 'files' => [UploadedFile::fake()->image('rx.jpg', 400, 400)]];
    }

    public function test_header_shows_city_picker_upload_button_(): void
    {
        $html = $this->get('/')->assertOk()->assertSee('data-fx-loc', false)->assertSee('Upload prescription')->assertDontSee('fx-trend', false)->getContent();
        $this->assertStringContainsString('id="fxLoc"', $html);
        $this->assertStringContainsString('Karachi', $html);

        setting()->setMany(['sf.header.show_rx' => false, 'sf.header.show_location' => false]);
        $this->get('/')->assertDontSee('data-fx-loc', false)->assertDontSee('fx-rx-btn', false);
    }

    public function test_customer_can_send_a_prescription_without_an_order(): void
    {
        $this->user('pharmacist');   // someone to alert
        $this->get('/upload-prescription')->assertOk()->assertSee('Drop your prescription here');
        $this->post('/upload-prescription', $this->payload())->assertRedirect('/upload-prescription')->assertSessionHas('rx_sent', 'Sara');

        $r = PrescriptionRequest::firstOrFail();
        $this->assertSame(['Sara', 'new', 1], [$r->name, $r->status, $r->getMedia('files')->count()]);
        $this->get('/upload-prescription')->assertSee('Thank you');
        $this->assertDatabaseHas('admin_alerts', ['message' => 'New prescription from Sara (0300 1234567)']);
    }

    public function test_prescription_upload_is_validated(): void
    {
        $this->post('/upload-prescription', $this->payload(['files' => []]))->assertSessionHasErrors('files');
        $this->post('/upload-prescription', $this->payload(['phone' => 'abc']))->assertSessionHasErrors('phone');
        $this->post('/upload-prescription', $this->payload(['name' => '']))->assertSessionHasErrors('name');
        $this->post('/upload-prescription', $this->payload(['files' => [UploadedFile::fake()->create('x.exe', 10, 'application/octet-stream')]]))->assertSessionHasErrors('files.0');
        $this->post('/upload-prescription', $this->payload(['files' => array_fill(0, 6, UploadedFile::fake()->image('a.jpg'))]))->assertSessionHasErrors('files');
        $this->assertSame(0, PrescriptionRequest::count());
    }

    public function test_upload_page_is_off_when_the_prescription_module_is_off(): void
    {
        setting()->set('modules.prescriptions', false);
        $this->get('/upload-prescription')->assertNotFound();
        $this->post('/upload-prescription', $this->payload())->assertNotFound();
    }

    public function test_pharmacist_sees_requests_opens_files_and_updates_status(): void
    {
        $this->post('/upload-prescription', $this->payload());
        $r = PrescriptionRequest::firstOrFail();
        $m = $r->getFirstMedia('files');

        $this->get('/admin/prescription-requests')->assertRedirect();   // guests are sent to sign in
        $this->actingAs($this->user('pharmacist'));
        $this->get('/admin/prescription-requests')->assertOk()->assertSee('Sara')->assertSee('0300 1234567');
        $this->get("/admin/prescription-requests/{$r->id}/files/{$m->id}")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->put("/admin/prescription-requests/{$r->id}", ['status' => 'contacted'])->assertRedirect();
        $this->assertSame(['contacted', true], [$r->fresh()->status, $r->fresh()->handled_by !== null]);
        $this->put("/admin/prescription-requests/{$r->id}", ['status' => 'bogus'])->assertSessionHasErrors('status');

        $this->delete("/admin/prescription-requests/{$r->id}")->assertRedirect();
        $this->assertSoftDeleted($r);
    }

    public function test_staff_without_permission_cannot_open_requests(): void
    {
        $this->post('/upload-prescription', $this->payload());
        $r = PrescriptionRequest::firstOrFail();
        $this->actingAs($this->user('rider'));
        $this->get('/admin/prescription-requests')->assertForbidden();
        $this->get("/admin/prescription-requests/{$r->id}/files/".$r->getFirstMedia('files')->id)->assertForbidden();
        $this->put("/admin/prescription-requests/{$r->id}", ['status' => 'closed'])->assertForbidden();
    }

    public function test_cards_show_dosage_chips_and_search_still_finds_by_salt(): void
    {
        $this->med(['name' => 'Panadol', 'specs' => ['composition' => 'Paracetamol 500mg', 'dosage_form' => 'Tablet', 'strength' => '500mg']]);
        $this->med(['name' => 'Vitamin Shot']);

        $this->get('/shop')->assertSee('Tablet')->assertDontSee('fx-salt', false);   // the salt line is gone from cards; it stays on the product page and in search
        $this->get('/shop?q=paracetamol')->assertSee('Panadol')->assertDontSee('Vitamin Shot');
        $this->getJson('/search/suggest?q=paracetamol')->assertJsonPath('results.0.name', 'Panadol');
    }

    public function test_product_page_lists_same_composition_alternatives_cheapest_first(): void
    {
        $a = $this->med(['name' => 'Brand A', 'price' => 90, 'specs' => ['composition' => 'Paracetamol 500mg']]);
        $this->med(['name' => 'Brand B', 'price' => 60, 'specs' => ['composition' => 'paracetamol 500mg']]);
        $this->med(['name' => 'Brand C', 'price' => 40, 'specs' => ['composition' => 'Paracetamol 500mg']]);
        $this->med(['name' => 'Other Salt', 'price' => 10, 'specs' => ['composition' => 'Ibuprofen 400mg']]);

        $html = $this->get('/product/'.$a->slug)->assertOk()->assertSee('Same composition, other brands')->getContent();
        $rail = substr($html, strpos($html, 'Same composition, other brands'));
        $this->assertLessThan(strpos($rail, 'Brand B'), strpos($rail, 'Brand C'));   // cheaper first
        $this->assertStringNotContainsString('Other Salt', substr($rail, 0, strpos($rail, 'Related products') ?: strlen($rail)));

        $solo = $this->med(['name' => 'Solo', 'specs' => ['composition' => 'Unique Salt']]);
        $this->get('/product/'.$solo->slug)->assertDontSee('Same composition, other brands');
    }

    public function test_category_icons_follow_the_name(): void
    {
        $this->assertSame('bandaid', Storefront::categoryIcon('Pain & fever'));
        $this->assertSame('droplet-half', Storefront::categoryIcon('Diabetes care'));
        $this->assertSame('emoji-smile', Storefront::categoryIcon('Baby & mother care'));
        $this->assertSame('capsule-pill', Storefront::categoryIcon('Something else'));
    }

    public function test_default_home_has_pharmacy_sections(): void
    {
        $c = Category::create(['name' => 'Pain & fever', 'is_active' => true]);   // only categories with products are shown
        (new \App\Models\Product)->forceFill(['name' => 'Panadol', 'price' => 5, 'status' => 'active', 'track_stock' => false, 'category_id' => $c->id])->save();
        $this->get('/')->assertOk()->assertSee('100% genuine')->assertSee('Have a prescription? Just send a photo.')->assertSee('How ordering works')->assertSee('Shop by need');
    }

    private function review(Product $p, array $o = []): Review
    {
        return Review::create($o + ['product_id' => $p->id, 'name' => 'Ali', 'rating' => 5, 'title' => 'Great', 'body' => 'Fast delivery', 'status' => 'approved']);
    }

    public function test_home_reviews_wall_shows_only_approved_reviews_with_a_rating_summary(): void
    {
        $p = $this->med(['name' => 'Panadol']);
        $this->get('/')->assertDontSee('What our customers say');   // nothing to show yet

        $this->review($p, ['name' => 'Sara Ahmed', 'title' => 'Genuine stuff', 'rating' => 5, 'order_id' => null, 'reply' => 'Thank you Sara!']);
        $this->review($p, ['name' => 'Bilal Khan', 'rating' => 4, 'title' => 'Decent', 'body' => 'Late by an hour']);
        $this->review($p, ['name' => 'Pending Pete', 'status' => 'pending', 'title' => 'Not yet moderated']);
        $this->review($p, ['name' => 'Hidden Hana', 'status' => 'hidden', 'title' => 'Hidden one']);
        $this->review($p, ['name' => 'Angry Andy', 'rating' => 2, 'title' => 'Bad day', 'body' => 'Meh']);

        $html = $this->get('/')->assertOk()->assertSee('What our customers say')->assertSee('Sara Ahmed')->assertSee('Genuine stuff')->assertSee('Thank you Sara!')
            ->assertSee('Bilal Khan')->assertDontSee('Pending Pete')->assertDontSee('Hidden Hana')->assertDontSee('Angry Andy')->getContent();   // 2★ is below the default minimum of 4
        $this->assertStringContainsString('3 reviews', $html);        // the summary counts every approved review
        $this->assertStringContainsString('3.7', $html);              // (5 + 4 + 2) / 3
        $this->assertStringContainsString('fx-rev-bars', $html);
    }

    public function test_reviews_section_is_configurable_and_follows_the_module(): void
    {
        $p = $this->med(['name' => 'Panadol']);
        $this->review($p, ['name' => 'Low Lola', 'rating' => 3, 'title' => 'Average']);
        $this->actingAs($this->user('owner'));
        $section = HomeSection::where('type', 'reviews')->firstOrFail();

        $this->get('/')->assertDontSee('Low Lola');
        $this->put("/admin/home-sections/{$section->id}", ['title' => 'Voices', 'limit' => 6, 'min_rating' => 3, 'is_active' => 1])->assertRedirect();
        $this->get('/')->assertSee('Voices')->assertSee('Low Lola')->assertDontSee('fx-rev-sum', false);   // summary switched off (unticked)

        $this->put("/admin/home-sections/{$section->id}", ['title' => 'Voices', 'limit' => 6, 'min_rating' => 9])->assertSessionHasErrors('min_rating');

        setting()->set('modules.reviews', false);
        $this->get('/')->assertDontSee('Voices');
    }

    public function test_demo_data_adds_sample_reviews_and_removes_them_cleanly(): void
    {
        $demo = app(DemoData::class);
        $demo->load();
        $n = Review::count();
        $this->assertGreaterThan(3, $n);
        $this->get('/')->assertSee('What our customers say');

        $mine = $this->med(['name' => 'Mine']);
        $this->review($mine, ['name' => 'Real Rita']);
        $demo->remove();
        $this->assertSame(['Real Rita'], Review::pluck('name')->all());
    }
}
