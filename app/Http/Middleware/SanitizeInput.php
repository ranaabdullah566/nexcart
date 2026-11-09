<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cleans every incoming string before validation: removes NUL / control characters and invalid UTF-8,
 * trims the ends. Passwords are never touched. (Rich-text fields keep their markup; they are filtered on output.)
 */
class SanitizeInput
{
    private const SKIP = ['password', 'password_confirmation', 'current_password', 'code', '_token', 'nx_ts'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $request->merge($this->clean($request->input()));
        }

        return $next($request);
    }

    private function clean(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::SKIP, true)) {
                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->clean($value);
            } elseif (is_string($value)) {
                if (! mb_check_encoding($value, 'UTF-8')) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
                $data[$key] = trim($value);
            }
        }

        return $data;
    }
}
