<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scanners hunt for .env, .git, wp-login, phpmyadmin … A real visitor never asks for those.
 * Each probe is a strike; a few strikes ban the IP for an hour.
 */
class ProbeGuard
{
    private const PATTERN = '#(^|/)(\.env|\.git|\.svn|\.hg|\.ht|\.DS_Store|wp-admin|wp-login|wp-content|wp-includes|xmlrpc\.php|phpmyadmin|pma|adminer|vendor/phpunit|cgi-bin|boaform|actuator|\.aws|\.ssh|config\.json|composer\.(json|lock)|artisan|backup\.(sql|zip)|db\.sql|shell\.php|eval-stdin)#i';

    public function handle(Request $request, Closure $next): Response
    {
        $ip = (string) $request->ip();

        if (Cache::get("probe:ban:{$ip}")) {
            abort(403);
        }

        if (preg_match(self::PATTERN, '/'.ltrim(rawurldecode($request->path()), '/'))) {
            $strikes = (int) Cache::get("probe:strikes:{$ip}", 0) + 1;
            Cache::put("probe:strikes:{$ip}", $strikes, now()->addHour());
            if ($strikes >= 5) {
                Cache::put("probe:ban:{$ip}", true, now()->addHour());
            }
            abort(404);
        }

        return $next($request);
    }
}
