<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** A health-blog article: tips and guidance shown at /blog. */
class Article extends Model implements HasMedia
{
    use Audited;
    use HasSlug, HasTranslations, InteractsWithMedia, SoftDeletes;

    protected $fillable = ['title', 'slug', 'tag', 'author', 'excerpt', 'body', 'is_published', 'published_at', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'published_at' => 'datetime'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(720)->format('webp')->nonQueued();
        $this->addMediaConversion('wide')->width(1600)->format('webp')->nonQueued();
    }

    /** HasSlug builds the slug from `name`; for an article that is its title. */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['title'] ?? null;
    }

    /** Visible to visitors: published and not scheduled for the future. */
    public function scopeLive(Builder $q): Builder
    {
        return $q->where('is_published', true)->where(fn ($w) => $w->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function coverUrl(string $size = 'card'): ?string
    {
        $m = $this->getFirstMedia('cover');

        return $m ? ($m->hasGeneratedConversion($size) ? $m->getUrl($size) : $m->getUrl()) : null;
    }

    public function date(): Carbon
    {
        return $this->published_at ?? $this->created_at;
    }

    /** Whole minutes to read the body, at ~200 words per minute (at least 1). */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->trans('body'))) / 200));
    }

    public function summary(int $limit = 150): string
    {
        $text = $this->trans('excerpt') ?: strip_tags((string) $this->trans('body'));

        return Str::limit(trim(preg_replace('/\s+/', ' ', (string) $text)), $limit);
    }
}
