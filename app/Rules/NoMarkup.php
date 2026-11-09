<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Plain-text fields (names, addresses, notes, messages) must not carry HTML, script or link markup.
 * Output is escaped everywhere as well — this stops the junk from being stored and from reaching staff e-mails.
 */
class NoMarkup implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }
        if (preg_match('/<\s*\/?\s*[a-z!?\/]|javascript\s*:|data\s*:\s*text\/html|on[a-z]+\s*=\s*["\']|&#x?[0-9a-f]+;/i', $value)) {
            $fail(__('Please remove HTML or script code from :attribute.'));
        }
    }
}
