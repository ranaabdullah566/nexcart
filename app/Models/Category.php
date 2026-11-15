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

    /** The picture shown in the shop sidebar and category chips: the uploaded one, else a product from this category (so every category has a face). */
    public function imageUrl(): ?string
    {
        $path = static::imagePaths()[$this->id] ?? null;

        return $path ? url($path) : null;
    }

    /**
     * Picture path (not a full URL, so it follows whichever host the visitor is on) for every category, worked out once per
     * request from one cached map: a category page lists a hundred categories and must not run a query for each.
     *
     * @return array<int, string|null>
     */
    public static function imagePaths(): array
    {
        if (app()->bound('nx.category-image-map')) {
            return app('nx.category-image-map');   // one map per request (dropped whenever a picture is saved or deleted)
        }
        $key = 'cat-img-map-'.(int) static::max('id').'-'.(static::max('updated_at') ?? 0).'-'.(int) Media::max('id');

        $map = Cache::remember($key, 600, function () {
            $path = fn (?Media $m) => $m ? parse_url($m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl(), PHP_URL_PATH) : null;
            $own = Media::where('model_type', static::class)->where('collection_name', 'image')->orderBy('id')->get()->groupBy('model_id')->map(fn ($g) => $path($g->first()));

            $map = [];
            $missing = [];
            foreach (static::pluck('id') as $id) {
                if ($own->get($id)) {
                    $map[$id] = $own->get($id);
                } else {
                    $missing[] = $id;
                }
            }
            if ($missing) {
                // first product picture of each category (featured, then newest)
                $prods = Product::active()->whereIn('category_id', $missing)->whereHas('media')->with('media')->orderByDesc('is_featured')->orderByDesc('id')->get()->groupBy('category_id');
                foreach ($missing as $id) {
                    $p = $prods->get($id)?->first(fn ($x) => $x->thumbUrl());
                    $map[$id] = $p ? parse_url($p->thumbUrl(), PHP_URL_PATH) : null;
                }
            }

            return $map;
        });
        app()->instance('nx.category-image-map', $map);

        return $map;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** The label shown in pickers (admin product / home section / menu forms). One flat level, so it is just the name. */
    public function getPathAttribute(): string
    {
        return (string) $this->name;
    }

    /** Categories are one flat level, so a category's products are just its own (kept for callers that ask for "this and everything below"). */
    public function descendantIds(): array
    {
        return [$this->id];
    }
}
