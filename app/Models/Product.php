<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use Audited;

    /** Stock moves on every sale and has its own ledger (Inventory). */
    protected array $auditExcept = ['stock_qty'];

    use HasSlug, HasTranslations, InteractsWithMedia, SoftDeletes;

    public const STATUSES = ['draft', 'active', 'archived'];

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'type', 'status', 'short_description', 'description',
        'price', 'compare_price', 'cost', 'track_stock', 'stock_qty', 'low_stock_threshold',
        'requires_prescription', 'is_featured', 'specs',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2', 'compare_price' => 'decimal:2', 'cost' => 'decimal:2',
            'track_stock' => 'boolean', 'requires_prescription' => 'boolean', 'is_featured' => 'boolean',
            'specs' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isVariable(): bool
    {
        return $this->type === 'variable';
    }

    /** Everything a product card needs, loaded in one go. */
    public function scopeCard(Builder $q): Builder
    {
        return $q->with(['variants', 'media'])->withCount(['modifierGroups', 'approvedReviews'])->withAvg('approvedReviews', 'rating');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    /** Total sellable stock: sum of variants for variable products. */
    public function totalStock(): int
    {
        return $this->isVariable() ? (int) $this->variants->where('is_active', true)->sum('stock_qty') : $this->stock_qty;
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->totalStock() <= $this->low_stock_threshold;
    }

    /** Lowest price (variable products) — shown as "from". */
    public function displayPrice(): string
    {
        return (string) ($this->isVariable() && $this->variants->isNotEmpty() ? $this->variants->min('price') : $this->price);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(240)->height(240)->format('webp')->nonQueued();
    }

    public function thumbUrl(): ?string
    {
        $m = $this->getFirstMedia('images');

        return $m ? ($m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl()) : null;
    }
}
