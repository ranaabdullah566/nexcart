<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
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
            return response()->json(['results' => [], 'more' => null]);
        }

        $q = Product::active()->card();
        ShopController::search($q, $term);

        $results = $q->orderBy('name')->limit(6)->get()->map(fn (Product $p) => [
            'name' => $p->trans('name'),
            'price' => ($p->isVariable() ? __('from').' ' : '').money($p->displayPrice()),
            'image' => $p->thumbUrl(),
            'url' => route('store.product', $p->slug),
        ]);

        return response()->json(['results' => $results, 'more' => route('store.shop', ['q' => $term])]);
    }
}
