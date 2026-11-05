<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function show(): View
    {
        return view('store.cart', ['cart' => $this->cart->toArray()]);
    }

    public function json(): JsonResponse
    {
        return response()->json($this->cart->toArray());
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'variant_id' => ['nullable', 'integer'],
            'options' => ['nullable', 'array', 'max:30'], 'options.*' => ['integer'],
            'qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::active()->findOrFail($data['product_id']);
        $this->cart->add($product, $data['variant_id'] ?? null, $data['options'] ?? [], $data['qty']);

        return response()->json($this->cart->toArray());
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $data = $request->validate(['qty' => ['required', 'integer', 'min:0', 'max:99']]);
        $this->cart->update($key, $data['qty']);

        return response()->json($this->cart->toArray());
    }

    public function destroy(string $key): JsonResponse
    {
        $this->cart->remove($key);

        return response()->json($this->cart->toArray());
    }
}
