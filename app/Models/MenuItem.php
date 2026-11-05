<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class MenuItem extends Model
{
    use Audited;

    use HasTranslations, SoftDeletes;

    public const LOCATIONS = ['header', 'topbar', 'footer_shop', 'footer_help', 'footer_company', 'footer'];

    /** Friendly names; each location replaces the built-in links of that spot as soon as it holds one active link. */
    public const LABELS = ['header' => 'Menu bar', 'topbar' => 'Top bar (right side)', 'footer_shop' => 'Footer column 1', 'footer_help' => 'Footer column 2', 'footer_company' => 'Footer column 3', 'footer' => 'Footer legal links'];

    protected $fillable = ['location', 'label', 'type', 'url', 'page_id', 'category_id', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Where the link goes; null when its page or category no longer exists / is hidden. */
    public function href(): ?string
    {
        return match ($this->type) {
            'page' => $this->page?->is_published ? route('store.page', $this->page->slug) : null,
            'category' => $this->category?->is_active ? route('store.category', $this->category->slug) : null,
            default => $this->url,
        };
    }

    /** Visible links for a place in the storefront, already resolved. @return Collection<int, array{label:string,href:string}> */
    public static function resolved(string $location): Collection
    {
        return static::with(['page', 'category'])->where('location', $location)->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (self $m) => ['label' => $m->trans('label'), 'href' => $m->href()])->filter(fn ($l) => $l['href'])->values();
    }
}
