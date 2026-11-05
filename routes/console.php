<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('nexcart:send-abandoned-reminders')->hourly()->withoutOverlapping();
Schedule::command('nexcart:heartbeat')->everyMinute();
Schedule::command('nexcart:backup --scheduled')->dailyAt('02:30')->withoutOverlapping();
Schedule::call(fn () => \App\Models\Alert::where('created_at', '<', now()->subDays(90))->delete())->name('alerts-prune')->daily();
Schedule::command('nexcart:trash:purge --scheduled')->dailyAt('03:15');
