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

if (! function_exists('brand_logo')) {
    /** The store's own logo (Theme editor) or the NexCart default. */
    function brand_logo(): string
    {
        $path = setting('brand.logo');

        return $path ? asset('storage/'.$path) : asset('assets/images/logo.svg');
    }
}

if (! function_exists('brand_favicon')) {
    function brand_favicon(): string
    {
        $path = setting('brand.favicon') ?: setting('brand.logo');

        return $path ? asset('storage/'.$path) : asset('assets/images/favicon.svg');
    }
}

if (! function_exists('announcement')) {
    /** The announcement bar text in the visitor's language, or null when the bar is off. */
    function announcement(): ?array
    {
        if (! setting('announce.enabled') || blank(setting('announce.text'))) {
            return null;
        }
        $locale = app()->getLocale();
        $text = $locale !== 'en' && filled(setting('announce.text_'.$locale)) ? setting('announce.text_'.$locale) : setting('announce.text');

        return ['text' => $text, 'url' => setting('announce.url')];
    }
}

if (! function_exists('email_text')) {
    /** An e-mail paragraph the owner can customise (Email templates), per language, with :placeholders. */
    function email_text(string $key, string $default, array $replace = []): string
    {
        $locale = app()->getLocale();
        $text = ($locale !== 'en' ? setting("emails.{$key}_{$locale}") : null) ?: setting("emails.{$key}") ?: $default;

        return strtr($text, collect($replace)->mapWithKeys(fn ($v, $k) => [':'.ltrim($k, ':') => $v])->all());
    }
}

if (! function_exists('csp_nonce')) {
    /** Per-request nonce that lets our own inline <script> tags run under the Content-Security-Policy. */
    function csp_nonce(): string
    {
        return app()->bound('csp.nonce') ? app('csp.nonce') : tap(base64_encode(random_bytes(16)), fn ($n) => app()->instance('csp.nonce', $n));
    }
}

if (! function_exists('sf')) {
    /** Storefront option set in Admin → Storefront (bool / int / select values). */
    function sf(string $key): mixed
    {
        return \App\Support\Sf::get($key);
    }
}

if (! function_exists('sft')) {
    /** Storefront text in the visitor's language. */
    function sft(string $key): string
    {
        return \App\Support\Sf::text($key);
    }
}
