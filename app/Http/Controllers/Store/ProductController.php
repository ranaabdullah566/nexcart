<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\CatalogAttribute;
use App\Models\Product;
use App\Support\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(string $slug): View
    {
        $product = $this->find($slug);

        $related = Product::active()->card()->whereKeyNot($product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->latest('id')->limit(16)->get();

        // same salt / composition under other brand names (a pharmacy staple: cheaper or available alternatives)
        $salt = trim((string) ($product->specs['composition'] ?? ''));
        $alternatives = collect();
        if ($salt !== '' && $salt !== '—') {
            $alternatives = Product::active()->card()->whereKeyNot($product->id)->where('specs', 'like', '%'.addcslashes($salt, '%_\\').'%')
                ->limit(40)->get()
                ->filter(fn ($p) => mb_strtolower(trim((string) ($p->specs['composition'] ?? ''))) === mb_strtolower($salt))
                ->sortBy(fn ($p) => $p->displayPrice())->take(8)->values();
        }

        return view('store.product', $this->data($product) + ['related' => $related, 'alternatives' => $alternatives]);
    }

    /** Partial shown in the quick-view modal. */
    public function quick(Request $request, string $slug): View|RedirectResponse
    {
        // the partial has no <html>/<title>: opened directly (not by the modal), show the real product page instead
        if (! $request->ajax()) {
            return redirect()->route('store.product', $slug);
        }

        return view('store.quick', $this->data($this->find($slug)));
    }

    private function find(string $slug): Product
    {
        return Product::active()->with(['variants' => fn ($q) => $q->where('is_active', true), 'media', 'category.parent', 'brand'])
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
            'reviews' => BusinessProfile::hasModule('reviews') ? $product->approvedReviews()->latest('id')->limit(10)->get() : collect(),
            'rating' => BusinessProfile::hasModule('reviews') ? ['avg' => round((float) $product->approvedReviews()->avg('rating'), 1), 'count' => $product->approvedReviews()->count()] : ['avg' => 0, 'count' => 0],
        ];
    }
}
