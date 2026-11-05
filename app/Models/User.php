<?php

namespace App\Models;

use App\Mail\ResetPasswordMail;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\Audited;
use App\Support\Totp;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'default_address'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    use Audited;

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    use SoftDeletes;

    /** Anyone holding a staff role may use the admin area. */
    public function isStaff(): bool
    {
        return $this->roles()->exists();
    }

    /** Called by the password broker: send the branded e-mail (a failed e-mail never breaks the page). */
    public function sendPasswordResetNotification($token): void
    {
        try {
            Mail::to($this->email)->send(new ResetPasswordMail($this, (string) $token));
        } catch (\Throwable $e) {
            Log::warning('Password reset e-mail failed: '.$e->getMessage());
        }
    }

    /** A TOTP code, or one single-use recovery code. */
    public function checkTwoFactor(string $input): bool
    {
        if (! $this->two_factor_secret) {
            return false;
        }
        if (Totp::verify($this->two_factor_secret, $input)) {
            return true;
        }

        $codes = (array) $this->two_factor_recovery_codes;
        $needle = strtolower(trim($input));
        foreach ($codes as $i => $code) {
            if (hash_equals($code, $needle)) {
                unset($codes[$i]);
                $this->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'default_address' => 'array',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
