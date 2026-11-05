<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/** Third-party keys the owner enters in Admin → Security (secrets are stored encrypted). */
class Integrations
{
    public static function secret(string $key): ?string
    {
        $raw = setting("integrations.{$key}");
        if (! $raw) {
            return null;
        }
        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function setSecret(string $key, ?string $value): void
    {
        if (filled($value)) {
            setting()->set("integrations.{$key}", Crypt::encryptString($value));
        }
    }

    public static function recaptcha(): bool
    {
        return (bool) setting('integrations.recaptcha_enabled') && filled(setting('integrations.recaptcha_site')) && filled(static::secret('recaptcha_secret'));
    }

    public static function google(): bool
    {
        return (bool) setting('integrations.google_enabled') && filled(setting('integrations.google_client_id')) && filled(static::secret('google_client_secret'));
    }
}
