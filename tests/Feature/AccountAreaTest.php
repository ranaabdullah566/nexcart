<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PrescriptionRequest;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\UserAddress;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAreaTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        setting()->set('system.installed', true);
        $this->me = User::factory()->create(['name' => 'Sara Khan', 'phone' => '0300 1111111']);
    }

    private function order(string $no, string $status, int $user, string $tok): Order
    {
        return Order::forceCreate(['number' => $no, 'user_id' => $user, 'status' => $status, 'payment_status' => 'unpaid', 'payment_method' => 'cod', 'shipping_method' => 'Standard', 'total' => 100, 'subtotal' => 100, 'currency' => 'PKR', 'name' => 'Sara', 'email' => 's@example.com', 'phone' => '1', 'placed_at' => now(), 'public_token' => str_repeat($tok, 40)]);
    }

    private function addr(array $o = []): array
    {
        return $o + ['label' => 'Home', 'name' => 'Sara', 'phone' => '0300 1234567', 'address_line' => 'House 5', 'city' => 'Karachi'];
    }

    public function test_every_account_page_needs_login_and_renders_when_signed_in(): void
    {
        $paths = ['/account', '/account/orders', '/account/addresses', '/account/prescriptions', '/account/reviews', '/account/profile'];
        foreach ($paths as $p) {
            $this->get($p)->assertRedirect('/login');
        }
        $this->actingAs($this->me);
        foreach ($paths as $p) {
            $this->get($p)->assertOk()->assertSee('ac-side', false);
        }
    }

    public function test_address_book_with_default_syncing_to_checkout(): void
    {
        $this->actingAs($this->me);
        $this->post('/account/addresses', $this->addr())->assertRedirect('/account/addresses');
        $home = UserAddress::firstOrFail();
        $this->assertTrue($home->is_default);                                              // the first one becomes default
        $this->assertSame('Karachi', $this->me->fresh()->default_address['city']);

        $this->post('/account/addresses', $this->addr(['label' => 'Office', 'city' => 'Lahore']));
        $office = UserAddress::firstWhere('label', 'Office');
        $this->assertFalse($office->is_default);
        $this->post("/account/addresses/{$office->id}/default");
        $this->assertSame('Lahore', $this->me->fresh()->default_address['city']);
        $this->assertFalse($home->fresh()->is_default);

        $this->put("/account/addresses/{$office->id}", $this->addr(['label' => 'Office', 'city' => 'Multan', 'is_default' => 1]));
        $this->assertSame('Multan', $this->me->fresh()->default_address['city']);

        $this->delete("/account/addresses/{$office->id}");                                  // deleting the default promotes the other
        $this->assertTrue($home->fresh()->is_default);
        $this->assertSame('Karachi', $this->me->fresh()->default_address['city']);
        $this->delete("/account/addresses/{$home->id}");
        $this->assertNull($this->me->fresh()->default_address);

        $this->post('/account/addresses', $this->addr(['phone' => 'x']))->assertSessionHasErrors('phone');
    }

    public function test_people_cannot_touch_each_others_addresses(): void
    {
        $other = User::factory()->create();
        $theirs = UserAddress::create($this->addr() + ['user_id' => $other->id]);
        $this->actingAs($this->me);
        $this->get("/account/addresses/{$theirs->id}/edit")->assertNotFound();
        $this->put("/account/addresses/{$theirs->id}", $this->addr())->assertNotFound();
        $this->delete("/account/addresses/{$theirs->id}")->assertNotFound();
        $this->assertDatabaseHas('user_addresses', ['id' => $theirs->id]);
    }

    public function test_orders_page_filters_and_offers_reorder_and_invoice(): void
    {
        $this->order('MED-1', 'pending', $this->me->id, 'a');
        $this->order('MED-2', 'completed', $this->me->id, 'b');
        $this->order('MED-3', 'pending', User::factory()->create()->id, 'c');

        $this->actingAs($this->me);
        $this->get('/account/orders')->assertOk()->assertSee('MED-1')->assertSee('MED-2')->assertDontSee('MED-3')->assertSee('Order again')->assertSee('/invoice', false);
        $this->get('/account/orders?show=open')->assertSee('MED-1')->assertDontSee('MED-2');
        $this->get('/account/orders?show=done')->assertSee('MED-2')->assertDontSee('MED-1');
    }

    public function test_prescriptions_and_reviews_are_private_to_their_owner(): void
    {
        $other = User::factory()->create();
        $mine = PrescriptionRequest::create(['user_id' => $this->me->id, 'name' => 'Sara', 'phone' => '0300', 'status' => 'new']);
        $theirs = PrescriptionRequest::create(['user_id' => $other->id, 'name' => 'Zed', 'phone' => '0301', 'status' => 'new', 'notes' => 'secret-note']);
        $p = new Product;
        $p->forceFill(['name' => 'Panadol', 'price' => 5, 'status' => 'active', 'track_stock' => false])->save();
        Review::forceCreate(['product_id' => $p->id, 'user_id' => $this->me->id, 'name' => 'Sara', 'rating' => 4, 'body' => 'Works well', 'status' => 'approved']);
        Review::forceCreate(['product_id' => $p->id, 'user_id' => $other->id, 'name' => 'Zed', 'rating' => 1, 'body' => 'Not mine', 'status' => 'approved']);

        $this->actingAs($this->me);
        $this->get('/account/prescriptions')->assertOk()->assertDontSee('secret-note');
        $this->get("/account/prescriptions/{$theirs->id}/file/1")->assertNotFound();
        $this->get('/account/reviews')->assertOk()->assertSee('Works well')->assertDontSee('Not mine');
        $this->assertNotNull($mine->id);
    }

    public function test_profile_changes_need_the_current_password_for_email(): void
    {
        $this->actingAs($this->me);
        $this->put('/account', ['name' => 'Sara K', 'email' => $this->me->email, 'phone' => '0300 2222222'])->assertRedirect('/account/profile');
        $this->assertSame('Sara K', $this->me->fresh()->name);
        $this->put('/account', ['name' => 'Sara K', 'email' => 'new@example.com'])->assertSessionHasErrors('current_password');
    }
}
