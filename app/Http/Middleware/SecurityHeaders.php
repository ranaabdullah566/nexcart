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

        // Picture links follow the address the visitor used (127.0.0.1, localhost or the real domain), not only APP_URL:
        // an <img> from another host is blocked by img-src 'self' below. Queued jobs and e-mails keep APP_URL.
        config(['filesystems.disks.public.url' => $request->getSchemeAndHttpHost().'/storage']);
        app('filesystem')->forgetDisk('public');
        $response = $next($request);
        $h = $response->headers;

        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');   // the theme editor previews the store in a same-origin frame
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');
        $h->set('X-Permitted-Cross-Domain-Policies', 'none');
        $h->remove('X-Powered-By');
        if ($request->is('admin*', 'account*', 'checkout*', 'login*', 'register', 'order/*', 'track*') && ! $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            $h->set('Cache-Control', 'no-store, private');   // never let a shared computer's back button show someone's account
            $h->set('Pragma', 'no-cache');
        }
        if ($request->is('admin*', 'login*', 'register', 'account*', 'checkout*', 'order/*', 'track*', 'cart*')) {
            $h->set('X-Robots-Tag', 'noindex, nofollow');
        }
        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // The log viewer package ships its own inline scripts; it is owner-only and has its own protections.
        if (! $request->is('admin/log-viewer*') && ! $response instanceof BinaryFileResponse) {
            $captcha = \App\Support\Integrations::recaptcha() ? ' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/' : '';
            $h->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'{$captcha}",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data:",
                "connect-src 'self'",
                "frame-src 'self' https://www.google.com/maps https://www.google.com/maps/".($captcha ? ' https://www.google.com/recaptcha/ https://recaptcha.google.com/recaptcha/' : ''),
                "frame-ancestors 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self' https://checkout.stripe.com https://accounts.google.com",
            ]));
        }

        return $response;
    }
}
