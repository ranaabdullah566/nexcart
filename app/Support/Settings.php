<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * DB-backed settings with dot keys: "store.name", "theme.primary".
 * Everything the admin can customise lives here.
 */
class Settings
{
    private const CACHE_KEY = 'nexcart.settings';

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return Setting::query()->get()
                ->mapWithKeys(fn (Setting $s) => ["{$s->group}.{$s->key}" => $s->value])
                ->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? (config('nexcart.defaults')[$key] ?? $default);
    }

    public function set(string $key, mixed $value): void
    {
        [$group, $name] = explode('.', $key, 2);

        Setting::updateOrCreate(
            ['store_id' => null, 'group' => $group, 'key' => $name],
            ['value' => $value],
        );

        $this->flush();
    }

    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function group(string $group): array
    {
        $prefix = $group.'.';

        return collect($this->all())
            ->filter(fn ($v, $k) => str_starts_with($k, $prefix))
            ->mapWithKeys(fn ($v, $k) => [substr($k, strlen($prefix)) => $v])
            ->all();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function isInstalled(): bool
    {
        return (bool) $this->get('system.installed', false);
    }
}
