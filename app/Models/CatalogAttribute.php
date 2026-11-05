<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogAttribute extends Model
{
    use Audited;

    use HasTranslations, SoftDeletes;

    public const TYPES = ['text', 'number', 'select', 'boolean'];

    protected $fillable = ['key', 'label', 'type', 'options', 'business_types', 'is_required', 'is_filterable', 'sort_order'];

    protected function casts(): array
    {
        return ['options' => 'array', 'business_types' => 'array', 'is_required' => 'boolean', 'is_filterable' => 'boolean'];
    }

    /** Attributes that apply to the current business (null business_types = all). */
    public function scopeForBusiness($query, ?string $type = null)
    {
        $type ??= (string) setting('business.type', 'general');

        return $query->where(fn ($q) => $q->whereNull('business_types')->orWhereJsonContains('business_types', $type));
    }

    public function labelText(): string
    {
        return $this->trans('label');
    }
}
