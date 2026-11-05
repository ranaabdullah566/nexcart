<?php

use App\Support\Settings;

if (! function_exists('setting')) {
    function setting(?string $key = null, mixed $default = null): mixed
    {
        $settings = app(Settings::class);

        return $key === null ? $settings : $settings->get($key, $default);
    }
}
