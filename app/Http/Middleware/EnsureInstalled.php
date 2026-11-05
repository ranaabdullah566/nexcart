<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sends visitors to the setup wizard until the store has been installed. */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = setting()->isInstalled();

        if (! $installed && ! $request->is('setup', 'up')) {
            return redirect()->route('setup');
        }

        if ($installed && $request->is('setup')) {
            return redirect('/');
        }

        return $next($request);
    }
}
