<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Product cards for the "Recently viewed" row. The browser keeps the ids; the cards come from the server so they look exactly like every other card. */
class RecentController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $data = $request->validate(['ids' => ['required', 'string', 'max:200'], 'skip' => ['nullable', 'integer']]);
        $ids = collect(explode(',', $data['ids']))->map(fn ($i) => (int) trim($i))->filter(fn ($i) => $i > 0 && $i != ($data['skip'] ?? 0))->unique()->take(12)->values();
        $products = Product::active()->card()->with('category')->whereIn('id', $ids)->get()->keyBy('id');
        $html = $ids->map(fn ($id) => $products->get($id))->filter()->map(fn ($p) => '<div class="tq-rail-item">'.view('store._card', ['p' => $p])->render().'</div>')->implode('');

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
