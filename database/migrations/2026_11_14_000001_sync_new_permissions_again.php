<?php

use Database\Seeders\RolesSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Adds permissions introduced after install (customers.create, customers.export, alerts.view …) without touching roles an admin has customised. */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $new = [];
        foreach (RolesSeeder::PERMISSIONS as $group => $actions) {
            foreach ($actions as $a) {
                if (! Permission::where('name', "{$group}.{$a}")->exists()) {
                    $new[] = Permission::create(['name' => "{$group}.{$a}", 'guard_name' => 'web'])->name;
                }
            }
        }
        if ($new && ($owner = Role::where('name', 'owner')->first())) {
            $owner->givePermissionTo($new);
        }
        if ($new && ($manager = Role::where('name', 'manager')->first())) {
            $manager->givePermissionTo(array_filter($new, fn ($p) => ! str_starts_with($p, 'roles.')));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void {}
};
