<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Session cart. The session only stores ids and quantities — prices, names and stock are always read
 * from the database, so a tampered request can never change what something costs.
 */
class CartService
{
    private const KEY = 'cart';

    public function add(Product $product, ?int $variantId, int $qty): void
    {
        $this->assertSellable($product);
        $variant = $this->resolveVariant($product, $variantId);

        $key = md5($product->id.'|'.($variant?->id ?? 0));
        $cart = session(self::KEY, []);
        $newQty = ($cart[$key]['qty'] ?? 0) + $qty;

        $this->assertStock($product, $variant, $newQty, $key, $cart);

        $cart[$key] = ['product_id' => $product->id, 'variant_id' => $variant?->id, 'qty' => $newQty];
        session([self::KEY => $cart]);
    }

    public function update(string $key, int $qty): void
    {
        $cart = session(self::KEY, []);
        if (! isset($cart[$key])) {
            return;
        }
        if ($qty <= 0) {
            $this->remove($key);

            return;
        }

        $line = $cart[$key];
        $product = Product::active()->with('variants')->find($line['product_id']);
        if ($product) {
            $variant = $line['variant_id'] ? $product->variants->firstWhere('id', $line['variant_id']) : null;
            $this->assertStock($product, $variant, $qty, $key, $cart);
        }

        $cart[$key]['qty'] = $qty;
        session([self::KEY => $cart]);
    }

    public function remove(string $key): void
    {
        $cart = session(self::KEY, []);
        unset($cart[$key]);
        session([self::KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /** @return Collection<int, array> hydrated lines; lines whose product vanished are dropped */
    public function lines(): Collection
    {
        $cart = session(self::KEY, []);
        if (! $cart) {
            return collect();
        }

        $products = Product::active()->with(['variants', 'media'])->whereIn('id', collect($cart)->pluck('product_id'))->get()->keyBy('id');

        return collect($cart)->map(function ($line, $key) use ($products) {
            $product = $products->get($line['product_id']);
            $variant = $product && $line['variant_id'] ? $product->variants->firstWhere('id', $line['variant_id']) : null;
            if (! $product || ($line['variant_id'] && (! $variant || ! $variant->is_active))) {
                return null;
            }

            $unit = max(0.0, round((float) ($variant?->price ?? $product->price), 2));
            $available = $product->track_stock ? ($variant?->stock_qty ?? $product->stock_qty) : null;

            return [
                'key' => $key, 'product' => $product, 'variant' => $variant,
                'qty' => $line['qty'], 'unit' => $unit, 'total' => round($unit * $line['qty'], 2),
                'available' => $available, 'short' => $available !== null && $line['qty'] > $available,
            ];
        })->filter()->values();
    }

    public function count(): int
    {
        return (int) $this->lines()->sum('qty');
    }

    public function subtotal(): float
    {
        return round((float) $this->lines()->sum('total'), 2);
    }

    /** Shape consumed by the drawer / cart page JavaScript. */
    public function toArray(bool $withSuggestions = true): array
    {
        $lines = $this->lines();

        $savings = (float) $lines->sum(fn ($l) => ! $l['variant'] && $l['product']->compare_price && (float) $l['product']->compare_price > $l['unit'] ? ((float) $l['product']->compare_price - $l['unit']) * $l['qty'] : 0);
        $free = \App\Models\ShippingRate::where('is_active', true)->where('is_pickup', false)->whereNotNull('free_over')
            ->whereHas('zone', fn ($q) => $q->where('is_active', true))->min('free_over');

        return [
            'count' => (int) $lines->sum('qty'),
            'subtotal' => money($lines->sum('total')),
            'subtotal_raw' => round((float) $lines->sum('total'), 2),
            'savings' => $savings > 0 ? money($savings) : null,
            'savings_raw' => round($savings, 2),
            'free_over' => $free !== null ? (float) $free : null,
            'free_over_fmt' => $free !== null ? money((float) $free) : null,
            'suggest' => $withSuggestions ? $this->suggestions($lines) : [],
            'lines' => $lines->map(fn ($l) => [
                'key' => $l['key'], 'pid' => $l['product']->id, 'vid' => $l['variant']?->id,
                'name' => $l['product']->trans('name'),
                'variant' => $l['variant']?->name,
                'qty' => $l['qty'],
                'unit' => money($l['unit']),
                'total' => money($l['total']),
                'image' => $l['product']->thumbUrl(),
                'url' => route('store.product', $l['product']->slug),
                'short' => $l['short'],
                'available' => $l['available'],
                'wish' => $l['product']->id,
                'was' => (! $l['variant'] && $l['product']->compare_price && (float) $l['product']->compare_price > $l['unit']) ? money((float) $l['product']->compare_price * $l['qty']) : null,
                'rx' => (bool) $l['product']->requires_prescription,
            ])->all(),
        ];
    }

    /** Product models for the "Recommended for you" slider on the cart page. */
    public function recommended(int $limit = 10): Collection
    {
        $lines = $this->lines();
        $cats = $lines->pluck('product.category_id')->filter()->unique()->all();
        $q = Product::active()->card()->with('category')->whereNotIn('id', $lines->pluck('product.id')->all())
            ->where(fn ($w) => $w->where('track_stock', false)->orWhere('stock_qty', '>', 0)->orWhereHas('variants', fn ($v) => $v->where('stock_qty', '>', 0)));
        $first = $cats ? (clone $q)->whereIn('category_id', $cats)->limit($limit)->get() : collect();

        return $first->concat((clone $q)->whereNotIn('id', $first->pluck('id'))->orderByDesc('is_featured')->latest('id')->limit(max(0, $limit - $first->count()))->get())->values();
    }

    /** "Complete your order": a few in-stock products from the same categories as what is in the cart (or featured ones). */
    public function suggestions(Collection $lines, int $limit = 4): array
    {
        $inCart = $lines->pluck('product.id')->all();
        $cats = $lines->pluck('product.category_id')->filter()->unique()->all();

        $q = Product::active()->card()->whereNotIn('id', $inCart)
            ->where(fn ($w) => $w->where('track_stock', false)->orWhere('stock_qty', '>', 0)->orWhereHas('variants', fn ($v) => $v->where('stock_qty', '>', 0)));
        $picked = $cats ? (clone $q)->whereIn('category_id', $cats)->inRandomOrder()->limit($limit)->get() : collect();
        if ($picked->count() < $limit) {
            $picked = $picked->concat((clone $q)->whereNotIn('id', $picked->pluck('id'))->orderByDesc('is_featured')->latest('id')->limit($limit - $picked->count())->get());
        }

        return $picked->map(fn (Product $p) => [
            'id' => $p->id, 'name' => $p->trans('name'), 'price' => money($p->displayPrice()), 'image' => $p->thumbUrl(),
            'url' => route('store.product', $p->slug), 'options' => $p->isVariable(),
            'quick' => route('store.quick', $p->slug),
        ])->values()->all();
    }

    private function assertSellable(Product $product): void
    {
        if ($product->status !== 'active' || $product->trashed()) {
            throw ValidationException::withMessages(['product_id' => __('This product is not available.')]);
        }
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        if (! $product->isVariable()) {
            return null;
        }
        $variant = $variantId ? $product->variants()->where('is_active', true)->find($variantId) : null;
        if (! $variant) {
            throw ValidationException::withMessages(['variant_id' => __('Choose an option first.')]);
        }

        return $variant;
    }

    /** Total across lines that draw from the same stock (e.g. the same product added twice). */
    private function assertStock(Product $product, ?ProductVariant $variant, int $qty, string $key, array $cart): void
    {
        if ($product->max_per_order && $qty > $product->max_per_order) {
            throw ValidationException::withMessages(['qty' => __('You can order at most :n of this item.', ['n' => $product->max_per_order])]);
        }
        if (! $product->track_stock) {
            return;
        }
        $available = $variant?->stock_qty ?? $product->stock_qty;
        $others = collect($cart)->except($key)
            ->filter(fn ($l) => $l['product_id'] === $product->id && ($l['variant_id'] ?? null) === $variant?->id)->sum('qty');

        if ($qty + $others > $available) {
            throw ValidationException::withMessages(['qty' => $available > 0
                ? __('Only :n left in stock.', ['n' => $available])
                : __('Sorry, this is out of stock.')]);
        }
    }
}
