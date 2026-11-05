<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            if (blank($model->slug)) {
                $model->slug = static::uniqueSlug($model->name, $model->getKey());
            }
        });
    }

    /** Works for Urdu names too: falls back to a random handle when transliteration is empty. */
    public static function uniqueSlug(string $name, mixed $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'item-'.Str::lower(Str::random(6));
        $slug = $base;
        $i = 2;

        while (static::withoutGlobalScopes()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
