<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invisible bot trap for public forms: a hidden field humans never fill, and a signed timestamp —
 * a form submitted in under a second (or one that was never rendered) is a script, not a person.
 */
class Honeypot
{
    public function handle(Request $request, Closure $next): Response
    {
        // feature tests post straight to endpoints; the honeypot's own tests opt in with this header (never honoured outside tests)
        $skip = app()->runningUnitTests() && ! $request->hasHeader('X-Enforce-Honeypot');

        if ($request->isMethod('POST') && ! $skip) {
            $trap = filled($request->input('nx_website'));
            $age = null;
            try {
                $age = time() - (int) Crypt::decryptString((string) $request->input('nx_ts'));
            } catch (\Throwable) {
            }

            if ($trap || $age === null || $age < 1 || $age > 86400 * 2) {
                return $request->expectsJson()
                    ? response()->json(['message' => __('Please try again.')], 422)
                    : back()->withInput($request->except(['password', 'password_confirmation']))->with('error', __('That form was submitted too quickly. Please try again.'));
            }
        }

        return $next($request);
    }
}
