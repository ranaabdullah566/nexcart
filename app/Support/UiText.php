<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Owner-edited interface words ("Basket" instead of "Cart"). Stored as one JSON file per language and
 * loaded after lang/*.json, so an override beats the built-in translation — in every language including English.
 */
class UiText
{
    public static function dir(): string
    {
        return storage_path('app/ui-overrides');
    }

    public static function path(string $locale): string
    {
        return static::dir().'/'.$locale.'.json';
    }

    /** @return array<string, string> */
    public static function overrides(string $locale): array
    {
        $p = static::path($locale);

        return is_file($p) ? (array) json_decode((string) file_get_contents($p), true) : [];
    }

    public static function save(string $locale, array $map): void
    {
        File::ensureDirectoryExists(static::dir());
        $map = array_filter($map, fn ($v, $k) => is_string($k) && is_string($v) && trim($v) !== '', ARRAY_FILTER_USE_BOTH);
        if ($map === []) {
            File::delete(static::path($locale));

            return;
        }
        file_put_contents(static::path($locale), json_encode($map, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    /** All interface strings the owner can reword: every key of the built-in translation file. */
    public static function keys(): array
    {
        $base = json_decode((string) @file_get_contents(lang_path('ur.json')), true) ?: [];

        return array_keys($base);
    }

    /** Built-in translation of a key (before overrides) for a language. */
    public static function builtin(string $locale): array
    {
        $f = lang_path(str_replace('-', '_', $locale).'.json');

        return is_file($f) ? (array) json_decode((string) file_get_contents($f), true) : [];
    }
}
