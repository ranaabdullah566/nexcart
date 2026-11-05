<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\CatalogAttribute;
use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(string $slug): View
    {
        $product = $this->find($slug);

        $related = Product::active()->card()->whereKeyNot($product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->latest('id')->limit(4)->get();

        return view('store.product', $this->data($product) + ['related' => $related]);
    }

    /** Partial shown in the quick-view modal. */
    public function quick(string $slug): View
    {
        return view('store.quick', $this->data($this->find($slug)));
    }

    private function find(string $slug): Product
    {
        return Product::active()->with(['variants' => fn ($q) => $q->where('is_active', true), 'media', 'category.parent', 'brand', 'modifierGroups' => fn ($q) => $q->where('is_active', true), 'modifierGroups.options' => fn ($q) => $q->where('is_active', true)])
            ->where('slug', $slug)->firstOrFail();
    }

    private function data(Product $product): array
    {
        $labels = CatalogAttribute::forBusiness()->orderBy('sort_order')->get()->keyBy('key');
        $specs = collect($product->specs ?? [])->map(fn ($v, $k) => $labels->has($k) ? [
            'label' => $labels[$k]->labelText(),
            'value' => is_bool($v) ? ($v ? __('Yes') : __('No')) : $v,
        ] : null)->filter()->values();

        return [
            'product' => $product,
            'specs' => $specs,
            'inStock' => ! $product->track_stock || $product->totalStock() > 0,
        ];
    }
}
