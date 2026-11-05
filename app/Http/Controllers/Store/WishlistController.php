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
        return view('store.wishlist', ['products' => $this->wishlist->products()]);
    }

    public function toggle(int $product): JsonResponse
    {
        Product::active()->findOrFail($product);
        $added = $this->wishlist->toggle($product);

        return response()->json(['added' => $added, 'count' => count($this->wishlist->ids())]);
    }
}
