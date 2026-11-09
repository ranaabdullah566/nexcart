<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(private Wishlist $wishlist) {}

    public function index(): View
    {
        $products = $this->wishlist->products();
        $suggested = $products->isEmpty()
            ? Product::active()->card()->with('category')->orderByDesc('is_featured')->latest('id')->limit(4)->get()
            : collect();

        return view('store.wishlist', ['products' => $products, 'suggested' => $suggested]);
    }

    public function toggle(int $product): JsonResponse
    {
        Product::active()->findOrFail($product);
        $added = $this->wishlist->toggle($product);

        return response()->json(['added' => $added, 'count' => count($this->wishlist->ids())]);
    }
}
