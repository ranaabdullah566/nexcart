<?php

namespace App\Support;

class Locales
{
    /** @return array<string, array{label:string,dir:string,font:string}> */
    public static function all(): array
    {
        return config('nexcart.locales');
    }

    /** Locales the admin has enabled for the storefront. */
    public static function enabled(): array
    {
        $enabled = (array) setting('store.locales', ['en']);

        return array_intersect_key(static::all(), array_flip($enabled)) ?: ['en' => static::all()['en']];
    }

    public static function default(): string
    {
        $default = (string) setting('store.default_locale', 'en');

        return array_key_exists($default, static::all()) ? $default : 'en';
    }

    public static function dir(?string $locale = null): string
    {
        return static::all()[$locale ?? app()->getLocale()]['dir'] ?? 'ltr';
    }

    public static function isValid(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, static::all());
    }
}
