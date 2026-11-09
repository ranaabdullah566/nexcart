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
        'reports' => ['view', 'export', 'sales', 'products', 'customers', 'inventory', 'finance', 'operations', 'marketing', 'pharmacy'],
        'prescriptions' => ['view', 'verify'],
        'deliveries' => ['view', 'update'],
        'inventory' => ['view', 'adjust'],
        'catalog_settings' => ['manage'],   // product fields
        'storefront' => ['manage'],         // home page builder
        'sales_settings' => ['manage'],     // coupons, shipping
        'reviews' => ['manage'],            // moderate product reviews
        'system' => ['manage'],             // health, backups, license — owner only
        'activity' => ['view'],             // audit / activity log
        'trash' => ['restore', 'erase'],    // Recycle Bin
        'alerts' => ['view'],               // alerts inbox
        'roles' => ['manage'],              // create roles, edit permissions
    ];

    /**
     * Creates any missing permissions. Roles an owner has already shaped in Admin → Roles & permissions are left alone:
     * the owner role always gets everything, other existing roles only receive NEW permissions their defaults include.
     * Pass $reset = true to put the built-in roles back to their factory defaults.
     */
    public function run(bool $reset = false): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $existing = Permission::pluck('name')->all();
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

        $new = array_diff($all, $existing);
        foreach ($matrix as $name => $permissions) {
            $role = Role::where('name', $name)->where('guard_name', 'web')->first();
            if (! $role || $reset) {
                Role::findOrCreate($name, 'web')->syncPermissions($permissions);
            } elseif ($name === 'owner') {
                $role->syncPermissions($all);
            } elseif ($new) {
                $role->givePermissionTo(array_values(array_intersect($permissions, $new)));
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
