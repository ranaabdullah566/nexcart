<?php

/*
 * NexCart is a pharmacy-only online medicine store. The single "pharmacy"
 * profile is applied by the setup wizard; the admin can still override
 * modules, terms and theme afterwards.
 */
return [
    'pharmacy' => [
        'label' => 'Online Pharmacy',
        'description' => 'Medicines with prescription upload, batch & expiry tracking, delivery slots and riders.',
        'modules' => ['prescriptions', 'batch_expiry', 'variants', 'delivery_slots', 'coupons', 'shipping', 'pickup', 'riders', 'reviews'],
        'terms' => ['product' => 'Medicine', 'order' => 'Order', 'cart' => 'Cart'],
        'checkout_steps' => ['contact', 'shipping', 'prescription', 'payment'],
        'attributes' => [
            ['key' => 'composition', 'label' => 'Composition', 'type' => 'text'],
            ['key' => 'dosage_form', 'label' => 'Dosage form', 'type' => 'select', 'options' => ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream / Ointment', 'Drops', 'Other']],
            ['key' => 'strength', 'label' => 'Strength', 'type' => 'text'],
            ['key' => 'manufacturer', 'label' => 'Manufacturer', 'type' => 'text'],
        ],
        'settings' => ['theme.primary' => '#10b981', 'theme.accent' => '#38bdf8'],
    ],
];
