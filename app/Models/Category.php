<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
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
            $p = Product::active()->where('category_id', $this->id)->whereHas('media')->with('media')->orderByDesc('is_featured')->latest('id')->first();

            return $p?->thumbUrl();
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Categories are one flat level, so a category's products are just its own (kept for callers that ask for "this and everything below"). */
    public function descendantIds(): array
    {
        return [$this->id];
    }
}
