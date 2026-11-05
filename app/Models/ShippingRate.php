<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = ['shipping_zone_id', 'name', 'price', 'free_over', 'min_subtotal', 'eta', 'eta_minutes', 'is_pickup', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'free_over' => 'decimal:2', 'min_subtotal' => 'decimal:2', 'is_pickup' => 'boolean', 'is_active' => 'boolean'];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function isAvailableFor(float $subtotal): bool
    {
        return $this->is_active && ($this->min_subtotal === null || $subtotal >= (float) $this->min_subtotal);
    }

    public function costFor(float $subtotal): float
    {
        if ($this->is_pickup) {
            return 0.0;
        }

        return ($this->free_over !== null && $subtotal >= (float) $this->free_over) ? 0.0 : (float) $this->price;
    }
}
