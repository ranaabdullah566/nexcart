<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingZone extends Model
{
    use Audited;

    use SoftDeletes;

    protected $fillable = ['name', 'cities', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['cities' => 'array', 'is_active' => 'boolean'];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class)->orderBy('sort_order')->orderBy('id');
    }

    /** The zone serving a city: an explicit city match wins, otherwise the catch-all zone (no cities listed). */
    public static function forCity(?string $city): ?self
    {
        $zones = static::with('rates')->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        $needle = mb_strtolower(trim((string) $city));

        if ($needle !== '') {
            $hit = $zones->first(fn ($z) => collect($z->cities ?? [])->contains(fn ($c) => mb_strtolower(trim($c)) === $needle));
            if ($hit) {
                return $hit;
            }
        }

        return $zones->first(fn ($z) => empty($z->cities));
    }
}
