<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModifierOption extends Model
{
    use HasTranslations;

    protected $fillable = ['modifier_group_id', 'name', 'price_delta', 'is_default', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['price_delta' => 'decimal:2', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }
}
