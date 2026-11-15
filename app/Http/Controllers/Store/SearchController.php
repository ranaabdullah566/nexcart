<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /** Instant search dropdown. */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2 || mb_strlen($term) > 100) {
            return response()->json(['results' => [], 'categories' => [], 'more' => null]);
        }

        $q = Product::active()->card();
        ShopController::search($q, $term);

        $like = '%'.addcslashes($term, '%_\\').'%';
        $categories = Category::where('is_active', true)->where(fn ($c) => $c->where('name', 'like', $like)->orWhere('translations', 'like', $like))
            ->withCount(['products' => fn ($p) => $p->active()])->whereHas('products', fn ($p) => $p->active())
            ->orderBy('name')->limit(5)->get()->map(fn (Category $c) => [
                'name' => $c->trans('name'), 'count' => $c->products_count, 'image' => $c->imageUrl(), 'url' => route('store.category', $c->slug),
            ]);

        $results = $q->orderBy('name')->limit(8)->get()->map(fn (Product $p) => [
            'name' => $p->trans('name'),
            'price' => ($p->isVariable() ? __('from').' ' : '').money($p->displayPrice()),
            'image' => $p->thumbUrl(),
            'url' => route('store.product', $p->slug),
        ]);

        return response()->json(['results' => $results, 'categories' => $categories, 'more' => route('store.shop', ['q' => $term])]);
    }
}
