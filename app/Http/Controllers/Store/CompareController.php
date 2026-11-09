<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\CatalogAttribute;
use App\Models\Product;
use App\Services\Compare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function __construct(private Compare $compare) {}

    public function index(): View
    {
        $products = $this->compare->products();
        $attrs = CatalogAttribute::forBusiness()->orderBy('sort_order')->orderBy('label')->get()
            ->filter(fn ($a) => $products->contains(fn ($p) => filled($p->specs[$a->key] ?? null)))->values();

        return view('store.compare', ['products' => $products, 'attrs' => $attrs]);
    }

    public function toggle(int $product): JsonResponse
    {
        Product::active()->findOrFail($product);

        return response()->json($this->compare->toggle($product) + ['max' => Compare::MAX]);
    }

    public function clear(): RedirectResponse
    {
        $this->compare->clear();

        return redirect()->route('store.compare');
    }
}
