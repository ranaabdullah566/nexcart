<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One upload of the product sheet and what it did. */
class ProductSync extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['options' => 'array', 'errors' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
