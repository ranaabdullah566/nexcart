<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Category extends Model implements HasMedia
{
    use Audited, InteractsWithMedia;

    use HasSlug, HasTranslations, SoftDeletes;

    /** Levels allowed: category › sub-category › sub-sub-category. */
    public const MAX_DEPTH = 3;

    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'sort_order', 'is_active', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(200)->height(200)->format('webp')->nonQueued();
    }

    /** The picture shown in the shop sidebar and category chips: the uploaded one, else a product from this branch (so every category has a face). */
    public function imageUrl(): ?string
    {
        $m = $this->getFirstMedia('image');
        if ($m) {
            return $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl();
        }

        return Cache::remember('cat-img-'.$this->id.'-'.($this->updated_at?->timestamp ?? 0), 600, function () {
            $p = Product::active()->whereIn('category_id', $this->descendantIds())->with('media')->orderByDesc('is_featured')->latest('id')->get()->first(fn ($x) => $x->thumbUrl());

            return $p?->thumbUrl();
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** "Drinks › Cold drinks" */
    public function getPathAttribute(): string
    {
        return $this->parent ? $this->parent->path.' › '.$this->name : $this->name;
    }

    /** Ids of this category and every descendant. */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    /** 1 for a top-level category, 2 for its child, 3 for a grandchild. */
    public function depth(): int
    {
        $d = 1;
        for ($c = $this->parent; $c && $d < 10; $c = $c->parent) {
            $d++;
        }

        return $d;
    }

    /** How many levels this category's own subtree spans (1 = no children). */
    public function height(): int
    {
        return 1 + ($this->children->isEmpty() ? 0 : $this->children->max(fn ($c) => $c->height()));
    }
}
