<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\ServiceProvider;

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
        Json::encodeUsing(fn ($value) => json_encode($value, JSON_UNESCAPED_UNICODE));
    }
}
