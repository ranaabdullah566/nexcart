<?php

use App\Support\Settings;
use Illuminate\Support\Carbon;

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

if (! function_exists('local_time')) {
    /** Format a stored (UTC) timestamp in the store's own timezone. */
    function local_time(?Carbon $date, string $format = 'd M Y, H:i'): string
    {
        return $date ? $date->copy()->timezone(setting('store.timezone', config('app.timezone')))->format($format) : '';
    }
}
