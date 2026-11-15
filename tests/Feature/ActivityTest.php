<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_changes_are_logged_with_user_and_old_new_values_and_filterable(): void
    {
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        $owner = tap(User::factory()->create(['name' => 'Boss']), fn ($u) => $u->assignRole('owner'));
        $this->actingAs($owner);

        $b = Category::create(['name' => 'Acme']);
        $b->update(['name' => 'Acme 2']);
        $b->delete();
        $b->restore();

        $events = \Spatie\Activitylog\Models\Activity::where('subject_type', Category::class)->pluck('event')->all();
        $this->assertEqualsCanonicalizing(['created', 'updated', 'deleted', 'restored'], $events);
        $upd = \Spatie\Activitylog\Models\Activity::where('event', 'updated')->first();
        $this->assertSame('Acme', $upd->properties['old']['name']);
        $this->assertSame($owner->id, $upd->causer_id);

        $this->get('/admin/activity?event=deleted&user='.$owner->id)->assertOk()->assertSee('Category #'.$b->id, false)->assertDontSee('Acme 2 </', false);
        $this->get('/admin/activity?q=Acme')->assertOk()->assertSee('Boss');
        $this->get('/admin/activity/export')->assertOk();
    }

    public function test_stock_changes_do_not_flood_the_log(): void
    {
        $p = Product::create(['name' => 'P', 'slug' => 'p', 'type' => 'simple', 'status' => 'active', 'price' => 5, 'stock_qty' => 5]);
        $p = $p->fresh();
        $before = \Spatie\Activitylog\Models\Activity::count();
        $p->update(['stock_qty' => 4]);
        $this->assertSame($before, \Spatie\Activitylog\Models\Activity::count());
    }
}
