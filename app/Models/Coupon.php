<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use Audited;

    use SoftDeletes;

    public const TYPES = ['percent', 'fixed', 'free_shipping'];

    protected $fillable = ['code', 'type', 'value', 'min_subtotal', 'max_discount', 'starts_at', 'ends_at', 'usage_limit', 'is_active'];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2', 'min_subtotal' => 'decimal:2', 'max_discount' => 'decimal:2',
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean',
        ];
    }

    public static function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', static::normalize($code))->first();
    }

    /** @return string|null a customer-facing reason the coupon cannot be used, or null when it is fine */
    public function problem(float $subtotal): ?string
    {
        if (! $this->is_active) {
            return __('This code is not active.');
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return __('This code is not valid yet.');
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return __('This code has expired.');
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return __('This code has been fully used.');
        }
        if ($this->min_subtotal !== null && $subtotal < (float) $this->min_subtotal) {
            return __('Spend at least :amount to use this code.', ['amount' => money($this->min_subtotal)]);
        }

        return null;
    }

    /** Amount taken off the goods (free_shipping coupons discount shipping instead — see PricingService). */
    public function discountFor(float $subtotal): float
    {
        $amount = match ($this->type) {
            'percent' => $subtotal * min(100, (float) $this->value) / 100,
            'fixed' => (float) $this->value,
            default => 0.0,
        };
        if ($this->type === 'percent' && $this->max_discount !== null) {
            $amount = min($amount, (float) $this->max_discount);
        }

        return round(min($amount, $subtotal), 2);
    }

    public function label(): string
    {
        return match ($this->type) {
            'percent' => rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'%',
            'fixed' => money($this->value),
            default => __('Free shipping'),
        };
    }
}
