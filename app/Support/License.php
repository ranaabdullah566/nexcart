<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Offline license keys: "NXC.<payload>.<signature>", the payload being base64url JSON signed with Ed25519.
 * Nothing phones home. An unlicensed or expired copy keeps selling — it only shows a licence reminder
 * in the admin and a "Powered by NexCart" line in the storefront footer (soft enforcement).
 */
class License
{
    public const PREFIX = 'NXC';

    private static ?array $status = null;

    public static function enabled(): bool
    {
        return filled(config('license.public_key')) && function_exists('sodium_crypto_sign_verify_detached');
    }

    public static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public static function unb64(string $s): string|false
    {
        return base64_decode(strtr($s, '-_', '+/'), true);
    }

    /** Sign a payload with the vendor's private key (base64). Vendor machines only. */
    public static function issue(array $payload, string $privateKeyB64): string
    {
        $body = static::b64(json_encode($payload + ['v' => 1], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $sig = sodium_crypto_sign_detached($body, base64_decode($privateKeyB64, true));

        return static::PREFIX.'.'.$body.'.'.static::b64($sig);
    }

    /** @return array|null the payload when the signature is genuine, otherwise null */
    public static function verify(string $key): ?array
    {
        if (! static::enabled() || ! preg_match('/^'.static::PREFIX.'\.([A-Za-z0-9_-]+)\.([A-Za-z0-9_-]+)$/', trim($key), $m)) {
            return null;
        }
        $sig = static::unb64($m[2]);
        $pub = base64_decode((string) config('license.public_key'), true);
        if ($sig === false || $pub === false || strlen($pub) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return null;
        }
        if (! sodium_crypto_sign_verify_detached($sig, $m[1], $pub)) {
            return null;
        }
        $payload = json_decode((string) static::unb64($m[1]), true);

        return is_array($payload) ? $payload : null;
    }

    public static function domainMatches(string $pattern, string $host): bool
    {
        $pattern = strtolower(trim($pattern));
        $host = strtolower($host);
        if ($pattern === '*' || in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test') || str_ends_with($host, '.localhost')) {
            return true;
        }
        if (str_starts_with($pattern, '*.')) {
            $root = substr($pattern, 2);

            return $host === $root || str_ends_with($host, '.'.$root);
        }

        return $host === $pattern || $host === 'www.'.$pattern;
    }

    /** @return array{state: string, data: ?array, reason: ?string} state: disabled | missing | valid | grace | expired | invalid | domain */
    public static function status(bool $fresh = false): array
    {
        if (! $fresh && static::$status !== null) {
            return static::$status;
        }

        if (! static::enabled()) {
            return static::$status = ['state' => 'disabled', 'data' => null, 'reason' => null];
        }
        $key = (string) setting('system.license', '');
        if ($key === '') {
            return static::$status = ['state' => 'missing', 'data' => null, 'reason' => __('No license key has been entered.')];
        }
        $data = static::verify($key);
        if (! $data) {
            return static::$status = ['state' => 'invalid', 'data' => null, 'reason' => __('The license key is not valid.')];
        }

        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST) ?: (string) request()->getHost();
        if (! static::domainMatches((string) ($data['domain'] ?? '*'), $host)) {
            return static::$status = ['state' => 'domain', 'data' => $data, 'reason' => __('This license is for :domain, not :host.', ['domain' => $data['domain'] ?? '?', 'host' => $host])];
        }
        if (! empty($data['expires'])) {
            $end = Carbon::parse($data['expires'])->endOfDay();
            if ($end->isPast()) {
                $grace = $end->copy()->addDays((int) config('license.grace_days', 14))->isFuture();

                return static::$status = ['state' => $grace ? 'grace' : 'expired', 'data' => $data, 'reason' => __('The license expired on :date.', ['date' => $end->format('d M Y')])];
            }
        }

        return static::$status = ['state' => 'valid', 'data' => $data, 'reason' => null];
    }

    public static function reset(): void
    {
        static::$status = null;
    }

    /** Show the reminder in the admin. */
    public static function needsAttention(): bool
    {
        return ! in_array(static::status()['state'], ['disabled', 'valid'], true);
    }

    /** Show "Powered by NexCart" in the storefront footer. */
    public static function showBranding(): bool
    {
        return ! in_array(static::status()['state'], ['disabled', 'valid', 'grace'], true);
    }
}
