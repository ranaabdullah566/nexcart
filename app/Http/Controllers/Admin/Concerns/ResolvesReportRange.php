<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** One date-range picker for every report. Day boundaries follow the store's timezone. */
trait ResolvesReportRange
{
    public const RANGES = [
        'today' => 'Today', 'yesterday' => 'Yesterday', '7' => '7 days', '30' => '30 days', '90' => '90 days',
        'month' => 'This month', 'last_month' => 'Last month', 'year' => 'This year', '365' => '1 year', 'custom' => 'Custom',
    ];

    /** @return array{Carbon, Carbon, string} start / end in UTC and the preset used */
    private function range(Request $request): array
    {
        $tz = setting('store.timezone', config('app.timezone'));
        $data = $request->validate(['range' => ['nullable', 'in:'.implode(',', array_keys(self::RANGES))], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $key = $data['range'] ?? '30';
        $now = now($tz);

        if ($key === 'custom') {
            if (filled($data['from'] ?? null) && filled($data['to'] ?? null)) {
                $from = Carbon::parse($data['from'], $tz)->startOfDay();
                $to = Carbon::parse($data['to'], $tz)->endOfDay();
                if ($to->lt($from) || $from->diffInDays($to) > 366) {
                    $key = '30';
                }
            } else {
                $key = '30';
            }
        }

        [$from, $to] = match ($key) {
            'custom' => [$from, $to],
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            default => [$now->copy()->subDays((int) $key - 1)->startOfDay(), $now->copy()->endOfDay()],
        };

        return [$from->utc(), $to->utc(), $key];
    }
}
