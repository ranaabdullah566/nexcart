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
        'customers' => ['view', 'create', 'update', 'delete', 'export'],
        'staff' => ['view', 'create', 'update', 'delete'],
        'settings' => ['view', 'update'],
        'reports' => ['view', 'export', 'sales', 'products', 'customers', 'inventory', 'finance', 'operations', 'marketing'],
        'prescriptions' => ['view', 'verify'],
        'deliveries' => ['view', 'update'],
        'inventory' => ['view', 'adjust'],
        'catalog_settings' => ['manage'],   // attributes, modifiers
        'storefront' => ['manage'],         // home page builder
        'sales_settings' => ['manage'],     // coupons, shipping
        'reviews' => ['manage'],            // moderate product reviews
        'system' => ['manage'],             // health, backups, license — owner only
        'activity' => ['view'],             // audit / activity log
        'trash' => ['restore', 'erase'],    // Recycle Bin
        'alerts' => ['view'],               // alerts inbox
        'roles' => ['manage'],              // create roles, edit permissions
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
                fn ($p) => ! str_starts_with($p, 'settings.') && ! str_starts_with($p, 'system.') && ! str_starts_with($p, 'roles.') && $p !== 'staff.delete',
            )),
            'staff' => ['alerts.view', 'products.view', 'categories.view', 'inventory.view', 'orders.view', 'orders.update', 'customers.view'],
            'rider' => ['alerts.view', 'orders.view', 'deliveries.view', 'deliveries.update'],
            'pharmacist' => ['alerts.view', 'products.view', 'inventory.view', 'inventory.adjust', 'orders.view', 'prescriptions.view', 'prescriptions.verify'],
        ];

        foreach ($matrix as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }
    }
}
