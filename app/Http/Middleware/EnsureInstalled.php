<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sends visitors to the installer / setup wizard until the store has been installed. */
class EnsureInstalled
{
    public function __construct(private Installer $installer) {}

    public function handle(Request $request, Closure $next): Response
    {
        $installed = setting()->isInstalled();

        if (! $installed && ! $request->is('setup', 'install', 'up', 'lang/*')) {
            if ($this->installer->needsDatabase()) {
                return Installer::locked()
                    ? response()->view('errors.database', status: 503)       // an installed site whose database went away: never offer the installer
                    : redirect()->route('install');
            }

            return redirect()->route('setup');
        }

        if ($installed && $request->is('setup', 'install')) {
            return redirect('/');
        }

        return $next($request);
    }
}
