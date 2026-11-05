<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/** Security / admin audit trail on top of spatie/laravel-activitylog. Never throws. */
class Audit
{
    public static function log(string $event, array $props = [], ?Model $subject = null, $user = null): void
    {
        try {
            $user ??= auth()->user();
            $request = request();
            $a = activity('audit')->event($event)->withProperties($props + [
                'ip' => $request?->ip(),
                'ua' => mb_substr((string) $request?->userAgent(), 0, 160),
            ]);
            if ($user) {
                $a->causedBy($user);
            }
            if ($subject) {
                $a->performedOn($subject);
            }
            $a->log($event);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function recent(int $limit = 200)
    {
        return Activity::query()->with('causer')->latest('id')->limit($limit)->get();
    }
}
