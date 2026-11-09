<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Someone who signed up for health tips and offers from the footer. */
class Subscriber extends Model
{
    protected $fillable = ['email', 'token', 'is_active', 'unsubscribed_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'unsubscribed_at' => 'datetime'];
    }

    public static function signUp(string $email): self
    {
        $email = Str::lower(trim($email));
        $s = static::firstOrNew(['email' => $email]);
        $s->token = $s->token ?: Str::random(40);
        $s->is_active = true;   // signing up again after leaving re-subscribes
        $s->unsubscribed_at = null;
        $s->save();

        return $s;
    }

    public function unsubscribeUrl(): string
    {
        return route('store.unsubscribe', $this->token);
    }
}
