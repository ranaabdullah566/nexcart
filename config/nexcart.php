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
        'store.default_locale' => 'en',
        'store.locales' => ['en', 'ur', 'ur_Latn'],
    ],

    // Supported languages. Key = Laravel locale (lang/{key}.json). 'dir' drives RTL layout.
    'locales' => [
        'en' => ['label' => 'English', 'dir' => 'ltr', 'font' => "'Space Grotesk'"],
        'ur' => ['label' => 'اردو', 'dir' => 'rtl', 'font' => "'Noto Naskh Arabic'"],
        'ur_Latn' => ['label' => 'Roman Urdu', 'dir' => 'ltr', 'font' => "'Space Grotesk'"],
    ],

    // Roles seeded on install. Permissions are granted in RolesSeeder.
    'roles' => ['owner', 'manager', 'staff', 'rider', 'pharmacist'],
];
