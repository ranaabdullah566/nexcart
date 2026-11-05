<?php

return [
    /*
     | Base64 Ed25519 public key that license keys are verified against.
     | Leave empty while developing or running your own store: licensing is then switched off completely.
     | Vendors: run `php artisan nexcart:license:keys`, put the PUBLIC key here (or in NEXCART_LICENSE_PUBLIC_KEY)
     | before building a release, and keep the PRIVATE key off the server.
     */
    'public_key' => env('NEXCART_LICENSE_PUBLIC_KEY', ''),

    // Days after expiry during which the store only shows a reminder.
    'grace_days' => 14,
];
