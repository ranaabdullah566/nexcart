<?php

namespace App\Support;

use App\Models\CatalogAttribute;

class BusinessProfile
{
    public static function all(): array
    {
        return config('business_profiles', []);
    }

    public static function find(string $type): array
    {
        return static::all()[$type] ?? static::all()['general'];
    }

    public static function current(): array
    {
        return static::find((string) setting('business.type', 'general'));
    }

    /** Is a feature module enabled? Admin overrides win over the preset. */
    public static function hasModule(string $module): bool
    {
        $override = setting("modules.{$module}");

        return $override !== null ? (bool) $override : in_array($module, static::current()['modules'], true);
    }

    /** Apply a preset: stores business type, modules and theme tokens. */
    public static function apply(string $type): void
    {
        $profile = static::find($type);
        $settings = app(Settings::class);

        $settings->set('business.type', $type);
        $settings->set('business.terms', $profile['terms']);
        $settings->set('business.checkout_steps', $profile['checkout_steps']);
        $settings->setMany($profile['settings']);

        foreach ($profile['attributes'] ?? [] as $i => $def) {
            CatalogAttribute::firstOrCreate(['key' => $def['key']], [
                'label' => $def['label'], 'type' => $def['type'], 'options' => $def['options'] ?? null,
                'business_types' => [$type], 'is_required' => $def['required'] ?? false, 'sort_order' => $i,
            ]);
        }

        HomeDefaults::seed($type);

        $all = collect(static::all())->pluck('modules')->flatten()->unique();
        foreach ($all as $module) {
            $settings->set("modules.{$module}", in_array($module, $profile['modules'], true));
        }
    }
}
