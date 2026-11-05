<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/** Browser protections on every page, including a nonce-based Content-Security-Policy. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = csp_nonce();   // created before rendering so views and header agree
        $response = $next($request);
        $h = $response->headers;

        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');   // the theme editor previews the store in a same-origin frame
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');
        $h->set('X-Permitted-Cross-Domain-Policies', 'none');
        $h->remove('X-Powered-By');
        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // The log viewer package ships its own inline scripts; it is owner-only and has its own protections.
        if (! $request->is('admin/log-viewer*') && ! $response instanceof BinaryFileResponse) {
            $h->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data:",
                "connect-src 'self'",
                "frame-src 'self'",
                "frame-ancestors 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self' https://checkout.stripe.com",
            ]));
        }

        return $response;
    }
}
