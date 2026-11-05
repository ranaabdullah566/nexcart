<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** Asks the vendor's update feed whether a newer release exists. Never downloads or applies anything. */
class UpdateChecker
{
    public static function configured(): bool
    {
        return filled(config('nexcart.update_url'));
    }

    /** @return array{version:string,notes:?string,url:?string,newer:bool}|null null when not configured or the feed is unreachable */
    public static function latest(bool $fresh = false): ?array
    {
        if (! static::configured()) {
            return null;
        }
        if ($fresh) {
            Cache::forget('nexcart.update');
        }

        return Cache::remember('nexcart.update', now()->addHours(6), function () {
            try {
                $res = Http::timeout(5)->acceptJson()->get(config('nexcart.update_url'));
                $v = $res->successful() ? (string) $res->json('version') : '';
                if (! preg_match('/^\d+\.\d+\.\d+/', $v)) {
                    return null;
                }

                return [
                    'version' => $v, 'notes' => $res->json('notes'),
                    'url' => preg_match('#^https?://#', (string) $res->json('url')) ? $res->json('url') : null,
                    'newer' => version_compare($v, config('nexcart.version'), '>'),
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }
}
