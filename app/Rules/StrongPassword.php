<?php

namespace App\Rules;

use Illuminate\Validation\Rules\Password;

/** One password policy for every form: 10+ characters with letters and numbers. */
class StrongPassword
{
    public static function rule(): Password
    {
        return Password::min(10)->letters()->numbers();
    }
}
