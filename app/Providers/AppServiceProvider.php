<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Store Urdu text as real characters (not \u0627…) so JSON columns stay readable and searchable with LIKE.
        // server logs: owner only, no exceptions (not even in the local environment)
        LogViewer::auth(fn ($request) => (bool) $request->user()?->can('system.manage'));

        Json::encodeUsing(fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE));
    }
}
