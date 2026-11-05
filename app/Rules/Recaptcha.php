<?php

namespace App\Rules;

use App\Support\Integrations;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/** Google reCAPTCHA v2 check. Does nothing when the owner has not turned it on. */
class Recaptcha implements ValidationRule
{
    public static function enabled(): bool
    {
        return Integrations::recaptcha();
    }

    /** Rule list to merge into a validator: `'g-recaptcha-response' => Recaptcha::rules()` */
    public static function rules(): array
    {
        return self::enabled() ? ['required', 'string', new self] : ['nullable'];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $r = Http::asForm()->timeout(6)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => Integrations::secret('recaptcha_secret'), 'response' => $value, 'remoteip' => request()->ip(),
            ]);
            $ok = $r->ok() && $r->json('success') === true;
        } catch (\Throwable) {
            $ok = false;   // fail closed: if Google cannot be reached we do not wave bots through
        }
        if (! $ok) {
            $fail(__('Please confirm you are not a robot.'));
        }
    }
}
