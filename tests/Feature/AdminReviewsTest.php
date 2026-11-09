<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Support\BusinessProfile;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewsTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        BusinessProfile::apply('pharmacy');
        $this->actingAs(tap(User::factory()->create(), fn ($u) => $u->assignRole('owner')));
        $this->product = new Product;
        $this->product->forceFill(['name' => 'Panadol', 'price' => 5, 'status' => 'active', 'track_stock' => false])->save();
    }

    public function test_staff_can_add_edit_and_publish_a_review(): void
    {
        $this->get('/admin/reviews/create')->assertOk()->assertSee('Panadol');
        $this->post('/admin/reviews', ['product_id' => $this->product->id, 'name' => 'Ayesha K.', 'rating' => 5, 'title' => 'Works fast', 'body' => 'Relief within the hour.', 'status' => 'approved', 'date' => '2026-10-01'])
            ->assertRedirect(route('admin.reviews.index', ['status' => 'all']));

        $r = Review::firstOrFail();
        $this->assertNotNull($r->added_by);
        $this->assertNull($r->order_id);
        $this->assertSame('2026-10-01', $r->created_at->toDateString());
        $this->get('/product/'.$this->product->slug)->assertOk()->assertSee('Relief within the hour.');

        $this->get("/admin/reviews/{$r->id}/edit")->assertOk()->assertSee('Ayesha K.');
        $this->put("/admin/reviews/{$r->id}", ['name' => 'Ayesha Khan', 'rating' => 4, 'title' => 'Good', 'body' => 'Edited', 'status' => 'hidden'])->assertSessionHasNoErrors();
        $this->assertSame(['Ayesha Khan', 4, 'hidden'], [$r->fresh()->name, $r->fresh()->rating, $r->fresh()->status]);
    }

    public function test_review_input_is_validated_and_customer_reviews_keep_their_words(): void
    {
        $base = ['product_id' => $this->product->id, 'name' => 'A', 'rating' => 5, 'status' => 'approved'];
        $this->post('/admin/reviews', ['rating' => 9] + $base)->assertSessionHasErrors('rating');
        $this->post('/admin/reviews', ['product_id' => 9999] + $base)->assertSessionHasErrors('product_id');
        $this->post('/admin/reviews', ['body' => '<script>alert(1)</script>'] + $base)->assertSessionHasErrors('body');
        $this->post('/admin/reviews', ['date' => now()->addDay()->toDateString()] + $base)->assertSessionHasErrors('date');
        $this->assertSame(0, Review::count());

        $customers = Review::forceCreate(['product_id' => $this->product->id, 'name' => 'Zed', 'rating' => 2, 'body' => 'Original words', 'status' => 'pending']);
        $this->put("/admin/reviews/{$customers->id}", ['status' => 'approved', 'body' => 'Changed by staff', 'name' => 'Hacked', 'rating' => 5])->assertSessionHasNoErrors();
        $this->assertSame(['Original words', 'Zed', 2, 'approved'], [$customers->fresh()->body, $customers->fresh()->name, $customers->fresh()->rating, $customers->fresh()->status]);
    }

    public function test_staff_without_permission_cannot_add_reviews(): void
    {
        $this->post('/logout');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/reviews/create')->assertForbidden();
        $this->post('/admin/reviews', ['product_id' => $this->product->id, 'name' => 'A', 'rating' => 5, 'status' => 'approved'])->assertForbidden();
    }

    public function test_dashboard_renders_with_orders_reviews_and_customers(): void
    {
        $o = \App\Models\Order::forceCreate(['number' => 'T-1', 'status' => 'completed', 'payment_status' => 'paid', 'payment_method' => 'cod', 'shipping_method' => 'Standard', 'name' => 'Sara', 'email' => 's@example.com', 'phone' => '1', 'city' => 'Lahore', 'subtotal' => 50, 'total' => 50, 'currency' => 'PKR', 'placed_at' => now(), 'public_token' => str_repeat('a', 40)]);
        \App\Models\OrderItem::forceCreate(['order_id' => $o->id, 'product_id' => $this->product->id, 'name' => 'Panadol', 'unit_price' => 5, 'qty' => 10, 'total' => 50]);
        Review::forceCreate(['product_id' => $this->product->id, 'name' => 'Zed', 'rating' => 4, 'body' => 'ok', 'status' => 'approved']);
        User::factory()->create();

        $this->get('/admin')->assertOk()->assertSee('Sales trend')->assertSee('Top products')->assertSee('Customer ratings')->assertSee('cTrend', false)->assertSee('Lahore', false);
    }
}
