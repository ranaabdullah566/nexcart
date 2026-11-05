<?php

namespace App\Models\Concerns;

/**
 * Per-locale copies of text fields, stored in a `translations` JSON column:
 * {"ur": {"name": "..."}, "ur_Latn": {"name": "..."}}. English lives in the plain column.
 */
trait HasTranslations
{
    public function initializeHasTranslations(): void
    {
        $this->casts['translations'] = 'array';
    }

    public function trans(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $value = $this->translations[$locale][$field] ?? null;

        return filled($value) ? $value : $this->getAttribute($field);
    }

    /** Keep only known locales/fields with non-empty text. */
    public function setTranslationsFrom(?array $input, array $fields): void
    {
        $clean = [];
        foreach ((array) $input as $locale => $values) {
            if (! array_key_exists($locale, config('nexcart.locales')) || $locale === 'en') {
                continue;
            }
            foreach ($fields as $field) {
                $text = trim((string) ($values[$field] ?? ''));
                if ($text !== '') {
                    $clean[$locale][$field] = $text;
                }
            }
        }

        $this->translations = $clean ?: null;
    }
}
