<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One of a customer's saved delivery addresses. The default one is mirrored into users.default_address (used by checkout). */
class UserAddress extends Model
{
    public const MAX_PER_USER = 10;

    protected $fillable = ['user_id', 'label', 'name', 'phone', 'address_line', 'city', 'area', 'postal_code', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function oneLine(): string
    {
        return collect([$this->address_line, $this->area, $this->city, $this->postal_code])->filter()->implode(', ');
    }

    /** Make this the default address and mirror it for checkout. */
    public function makeDefault(): void
    {
        self::where('user_id', $this->user_id)->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->forceFill(['is_default' => true])->save();
        $this->user->forceFill(['default_address' => array_filter($this->only(['address_line', 'city', 'area', 'postal_code']))])->save();
    }
}
