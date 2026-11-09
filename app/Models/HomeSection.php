<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class HomeSection extends Model implements HasMedia
{
    use Audited;
    use HasTranslations, InteractsWithMedia, SoftDeletes;

    public const TYPES = ['hero', 'categories', 'products', 'brands', 'banner', 'cta', 'features', 'steps', 'testimonials', 'reviews', 'blog', 'faq', 'text'];

    protected $fillable = ['type', 'title', 'settings', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'is_active' => 'boolean'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('wide')->width(1600)->format('webp')->nonQueued();
    }

    public function imageUrl(): ?string
    {
        $m = $this->getFirstMedia('image');

        return $m ? ($m->hasGeneratedConversion('wide') ? $m->getUrl('wide') : $m->getUrl()) : null;
    }

    /** Setting value, localised when a translation exists (translations[locale][key]). */
    public function text(string $key, ?string $default = null): ?string
    {
        $locale = app()->getLocale();
        $translated = $this->translations[$locale][$key] ?? null;

        return filled($translated) ? $translated : ($this->settings[$key] ?? $default);
    }
}
