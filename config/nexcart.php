<?php

return [
    'version' => trim((string) @file_get_contents(base_path('VERSION'))) ?: '0.0.0',

    // Where "is there a newer version?" looks. Expected JSON: {"version": "1.2.0", "notes": "…", "url": "https://…"}
    'update_url' => env('NEXCART_UPDATE_URL'),

    // Where the installer writes the environment file (tests point this somewhere harmless).
    'env_path' => env('NEXCART_ENV_PATH'),

    // The file whose presence means "installed — the web installer must never run again".
    'lock_file' => env('NEXCART_LOCK_FILE'),

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
        'store.phone_code' => '92',
        'theme.font' => 'space',
        'growth.loyalty' => false,
        'growth.earn_per' => 100,
        'growth.point_value' => 1,
        'growth.min_redeem' => 50,
        'growth.abandoned' => true,
        'growth.abandoned_hours' => 2,
        'growth.reviews_auto' => false,
        'emails.status_updates' => true,
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
