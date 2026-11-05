<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class DeliverySlot extends Model
{
    use SoftDeletes;

    protected $fillable = ['label', 'starts_at', 'ends_at', 'capacity', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function range(): string
    {
        return $this->starts_at.'–'.$this->ends_at;
    }

    public function displayLabel(): string
    {
        return $this->label.' · '.$this->range();
    }

    /** When this slot begins on a given date, in the store's timezone. */
    public function startsOn(Carbon $date): Carbon
    {
        [$h, $m] = array_map('intval', explode(':', $this->starts_at));

        return $date->copy()->setTime($h, $m);
    }
}
