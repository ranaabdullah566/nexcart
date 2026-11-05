<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_change_store_theme_and_modules(): void
    {
        (new RolesSeeder)->run();
        setting()->set('system.installed', true);
        $owner = tap(User::factory()->create(), fn ($u) => $u->assignRole('owner'));

        Livewire::actingAs($owner)->test(ManageSettings::class)
            ->fillForm([
                'store.name' => 'Burger Hub',
                'theme.primary' => '#ff5500',
                'modules.kitchen_display' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Burger Hub', setting('store.name'));
        $this->assertSame('#ff5500', setting('theme.primary'));
        $this->assertTrue((bool) setting('modules.kitchen_display'));
    }
}
