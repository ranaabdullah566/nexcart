<?php

namespace App\Http\Middleware;

use App\Support\Audit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Staff sessions: sign out after inactivity, and optionally force two-factor for everyone on the team. */
class SecureStaffSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $idle = (int) setting('security.idle_minutes', 120);
        $last = (int) $request->session()->get('nx_last_seen', time());
        if ($idle > 0 && time() - $last > $idle * 60) {
            Audit::log('auth.idle_logout', [], null, $user);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => __('Your session expired. Please sign in again.')], 401)
                : redirect()->route('login')->with('error', __('You were signed out after a period of inactivity.'));
        }
        $request->session()->put('nx_last_seen', time());

        if (setting('security.require_2fa') && ! $user->two_factor_confirmed_at && ! $request->routeIs('admin.security.*', 'logout')) {
            return $request->expectsJson()
                ? response()->json(['message' => __('Set up two-factor authentication first.')], 403)
                : redirect()->route('admin.security.index')->with('error', __('Your team requires two-factor authentication. Please turn it on to continue.'));
        }

        return $next($request);
    }
}
