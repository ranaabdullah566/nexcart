<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public const SORTS = ['newest', 'price_asc', 'price_desc', 'name', 'discount'];

    public function index(Request $request, ?string $category = null): View
    {
        $current = $category ? Category::where('is_active', true)->where('slug', $category)->firstOrFail() : null;
        $filterAttrs = CatalogAttribute::forBusiness()->where('is_filterable', true)->whereIn('type', ['select', 'boolean'])->get();

        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'price_min' => ['nullable', 'numeric', 'min:0'], 'price_max' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'], 'on_sale' => ['nullable', 'boolean'], 'featured' => ['nullable', 'boolean'],
            'type' => ['nullable', 'array'], 'type.*' => ['in:rx,otc'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
            'spec' => ['nullable', 'array'],
        ]);

        $query = Product::active()->card()->with('category');

        if ($current) {
            $query->whereIn('category_id', $current->descendantIds());
        }
        if (filled($f['q'] ?? null)) {
            $this->search($query, $f['q']);
        }
        if (isset($f['price_min'])) {
            $query->where('price', '>=', $f['price_min']);
        }
        if (isset($f['price_max'])) {
            $query->where('price', '<=', $f['price_max']);
        }
        if (! empty($f['in_stock'])) {
            $this->inStock($query);
        }
        if (! empty($f['on_sale'])) {
            $query->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price');
        }
        if (! empty($f['featured'])) {
            $query->where('is_featured', true);
        }
        $types = array_unique($f['type'] ?? []);
        if (count($types) === 1) {   // both ticked = no restriction
            $query->where('requires_prescription', $types[0] === 'rx');
        }
        foreach ($filterAttrs as $attr) {
            $value = $f['spec'][$attr->key] ?? null;
            if ($value !== null && $value !== '') {
                $query->where("specs->{$attr->key}", $attr->type === 'boolean' ? (bool) $value : $value);
            }
        }

        match ($f['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            'discount' => $query->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price')->orderByRaw('(compare_price - price) / compare_price desc'),
            default => $query->latest('id'),
        };

        $base = Product::active();
        if ($current) {
            $base->whereIn('category_id', $current->descendantIds());
        }

        $title = $current ? $current->trans('name') : (filled($f['q'] ?? null) ? __('Search results for “:q”', ['q' => $f['q']]) : __('All products'));

        return view('store.shop', [
            'title' => $title,
            'products' => $query->paginate((int) sf('cards.per_page'))->withQueryString(),
            'current' => $current,
            'filterAttrs' => $filterAttrs,
            'priceBounds' => ['min' => (float) (clone $base)->min('price'), 'max' => (float) (clone $base)->max('price')],
            'f' => $f,
            'subcategories' => collect(),
        ]);
    }

    /** Name, SKU, descriptions and the Urdu / Roman Urdu translations. */
    public static function search(Builder $query, string $term): void
    {
        $like = '%'.addcslashes(trim($term), '%_\\').'%';

        $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('barcodes', 'like', $like)
            ->orWhere('short_description', 'like', $like)->orWhere('specs', 'like', $like)->orWhere('translations', 'like', $like)
            ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like))
            ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', $like)));
    }

    private function inStock(Builder $query): void
    {
        $query->where(fn ($q) => $q->where('track_stock', false)
            ->orWhere(fn ($s) => $s->where('type', 'simple')->where('stock_qty', '>', 0))
            ->orWhereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock_qty', '>', 0)));
    }
}
