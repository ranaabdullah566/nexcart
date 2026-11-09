<?php

use Database\Seeders\RolesSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Adds reports.pharmacy and gives it to every role that can already see another report. */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $perm = Permission::findOrCreate('reports.pharmacy', 'web');
        foreach (Role::all() as $role) {
            if ($role->name === 'owner' || $role->hasPermissionTo('reports.marketing')) {
                $role->givePermissionTo($perm);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void {}
};
