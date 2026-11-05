<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /** Permission groups; later phases append their own resources here. */
    public const PERMISSIONS = [
        'products' => ['view', 'create', 'update', 'delete'],
        'categories' => ['view', 'create', 'update', 'delete'],
        'orders' => ['view', 'update', 'refund', 'delete'],
        'customers' => ['view', 'update', 'delete'],
        'staff' => ['view', 'create', 'update', 'delete'],
        'settings' => ['view', 'update'],
        'reports' => ['view'],
        'prescriptions' => ['view', 'verify'],
        'deliveries' => ['view', 'update'],
        'inventory' => ['view', 'adjust'],
        'catalog_settings' => ['manage'],   // attributes, modifiers
        'storefront' => ['manage'],         // home page builder
        'sales_settings' => ['manage'],     // coupons, shipping
        'reviews' => ['manage'],            // moderate product reviews
        'system' => ['manage'],             // health, backups, license — owner only
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = [];
        foreach (self::PERMISSIONS as $group => $actions) {
            foreach ($actions as $action) {
                $all[] = Permission::findOrCreate("{$group}.{$action}", 'web')->name;
            }
        }

        $matrix = [
            'owner' => $all,
            'manager' => array_values(array_filter(
                $all,
                fn ($p) => ! str_starts_with($p, 'settings.') && ! str_starts_with($p, 'system.') && $p !== 'staff.delete',
            )),
            'staff' => ['products.view', 'categories.view', 'inventory.view', 'orders.view', 'orders.update', 'customers.view'],
            'rider' => ['orders.view', 'deliveries.view', 'deliveries.update'],
            'pharmacist' => ['products.view', 'inventory.view', 'inventory.adjust', 'orders.view', 'prescriptions.view', 'prescriptions.verify'],
        ];

        foreach ($matrix as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }
}
