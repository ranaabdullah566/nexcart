<?php

use App\Support\Settings;

if (! function_exists('setting')) {
    function setting(?string $key = null, mixed $default = null): mixed
    {
        $settings = app(Settings::class);

        return $key === null ? $settings : $settings->get($key, $default);
    }
}

if (! function_exists('money')) {
    /** "Rs 1,250.00" using the store's currency symbol. */
    function money(float|int|string|null $amount): string
    {
        return setting('store.currency_symbol', 'Rs').' '.number_format((float) $amount, 2);
    }
}
