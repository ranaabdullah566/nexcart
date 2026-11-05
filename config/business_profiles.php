<?php

/*
 * Business profiles = presets applied by the setup wizard.
 * Same codebase, different behaviour: modules, terminology, checkout steps, theme.
 * Admin can still override every value afterwards.
 */
return [
    'general' => [
        'label' => 'General / Online Store',
        'description' => 'Fashion, electronics, gifts, grocery — any product catalogue.',
        'modules' => ['variants', 'coupons', 'reviews', 'wishlist', 'shipping'],
        'terms' => ['product' => 'Product', 'order' => 'Order', 'cart' => 'Cart'],
        'checkout_steps' => ['contact', 'shipping', 'payment'],
        'settings' => ['theme.primary' => '#7c3aed', 'theme.accent' => '#22d3ee'],
    ],
    'fastfood' => [
        'label' => 'Fast Food / Restaurant',
        'description' => 'Menu items, add-ons, delivery zones, kitchen display.',
        'modules' => ['modifiers', 'delivery_zones', 'kitchen_display', 'pickup', 'coupons', 'riders'],
        'terms' => ['product' => 'Menu Item', 'order' => 'Order', 'cart' => 'Basket'],
        'checkout_steps' => ['contact', 'delivery_or_pickup', 'payment'],
        'attributes' => [
            ['key' => 'spice_level', 'label' => 'Spice level', 'type' => 'select', 'options' => ['Mild', 'Medium', 'Hot', 'Extra hot']],
            ['key' => 'prep_time_min', 'label' => 'Prep time (minutes)', 'type' => 'number'],
            ['key' => 'is_veg', 'label' => 'Vegetarian', 'type' => 'boolean'],
        ],
        'settings' => ['theme.primary' => '#f97316', 'theme.accent' => '#facc15'],
    ],
    'pharmacy' => [
        'label' => 'Pharmacy',
        'description' => 'Medicines with prescription upload, batch & expiry tracking.',
        'modules' => ['prescriptions', 'batch_expiry', 'delivery_slots', 'coupons', 'shipping'],
        'terms' => ['product' => 'Medicine', 'order' => 'Order', 'cart' => 'Cart'],
        'checkout_steps' => ['contact', 'shipping', 'prescription', 'payment'],
        'attributes' => [
            ['key' => 'composition', 'label' => 'Composition', 'type' => 'text', 'required' => true],
            ['key' => 'dosage_form', 'label' => 'Dosage form', 'type' => 'select', 'options' => ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream / Ointment', 'Drops', 'Other']],
            ['key' => 'strength', 'label' => 'Strength', 'type' => 'text'],
            ['key' => 'manufacturer', 'label' => 'Manufacturer', 'type' => 'text'],
        ],
        'settings' => ['theme.primary' => '#10b981', 'theme.accent' => '#38bdf8'],
    ],
    'medical_store' => [
        'label' => 'Medical Store',
        'description' => 'Medical equipment & supplies with optional prescriptions.',
        'modules' => ['prescriptions', 'variants', 'batch_expiry', 'shipping', 'coupons'],
        'terms' => ['product' => 'Item', 'order' => 'Order', 'cart' => 'Cart'],
        'checkout_steps' => ['contact', 'shipping', 'payment'],
        'attributes' => [
            ['key' => 'model_no', 'label' => 'Model no.', 'type' => 'text'],
            ['key' => 'warranty_months', 'label' => 'Warranty (months)', 'type' => 'number'],
        ],
        'settings' => ['theme.primary' => '#0ea5e9', 'theme.accent' => '#a78bfa'],
    ],
];
