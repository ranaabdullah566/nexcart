<?php

return [
    // Fallback values when a setting has not been saved yet.
    'defaults' => [
        'store.name' => 'NexCart',
        'store.tagline' => null,
        'store.email' => null,
        'store.phone' => null,
        'store.currency' => 'PKR',
        'store.currency_symbol' => 'Rs',
        'store.timezone' => 'Asia/Karachi',
        'store.tax_rate' => 0,
        'store.tax_inclusive' => false,
        'theme.primary' => '#7c3aed',
        'theme.accent' => '#22d3ee',
        'theme.radius' => '1rem',
        'theme.mode' => 'dark',
        'business.type' => 'general',
        'system.installed' => false,
    ],

    // Roles seeded on install. Permissions are granted in RolesSeeder.
    'roles' => ['owner', 'manager', 'staff', 'rider', 'pharmacist'],
];
