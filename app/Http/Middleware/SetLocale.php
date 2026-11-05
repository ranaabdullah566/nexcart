<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Picks the language: session choice → store default. English is the fallback. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $chosen = $request->session()->get('locale');

        $locale = Locales::isValid($chosen) ? $chosen : Locales::default();

        app()->setLocale($locale);

        return $next($request);
    }
}
