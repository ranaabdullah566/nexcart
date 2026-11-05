<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * DB-backed settings with dot keys: "store.name", "theme.primary".
 * Everything the admin can customise lives here.
 */
class Settings
{
    private const CACHE_KEY = 'nexcart.settings';

    private ?array $resolved = null;

    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        try {
            return $this->resolved = Cache::rememberForever(self::CACHE_KEY, function () {
                return Setting::query()->get()
                    ->mapWithKeys(fn (Setting $s) => ["{$s->group}.{$s->key}" => $s->value])
                    ->all();
            });
        } catch (\Throwable) {
            // Database/cache not ready (fresh clone, composer install, migrations pending):
            // fall back to config defaults instead of crashing artisan/composer.
            return [];
        }
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
        $this->resolved = null;
        Cache::forget(self::CACHE_KEY);
    }

    public function isInstalled(): bool
    {
        return (bool) $this->get('system.installed', false);
    }
}
