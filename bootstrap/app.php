<?php

use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Support\FirstRun;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// A fresh upload should work without a terminal: create .env and an app key on the first web request.
if (PHP_SAPI !== 'cli') {
    FirstRun::ensureEnvironment(dirname(__DIR__));
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['staff' => EnsureStaff::class]);
        $middleware->redirectUsersTo('/admin');
        $middleware->web(append: [SetLocale::class, EnsureInstalled::class, SecurityHeaders::class]);
        // behind a reverse proxy / Docker the real client scheme and IP arrive in forwarded headers
        $middleware->trustProxies(at: env('TRUSTED_PROXIES') ?: null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
