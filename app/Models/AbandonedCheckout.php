<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbandonedCheckout extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['items' => 'array', 'total' => 'decimal:2', 'last_seen_at' => 'datetime', 'reminded_at' => 'datetime', 'recovered_at' => 'datetime', 'opted_out' => 'boolean'];
    }

    public function recoverUrl(): string
    {
        return route('store.cart.recover', $this->token);
    }

    public function stopUrl(): string
    {
        return route('store.cart.recover.stop', $this->token);
    }
}
