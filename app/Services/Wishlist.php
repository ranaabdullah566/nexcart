<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/** Guest wishlist kept in the session (becomes per-customer when accounts arrive). */
class Wishlist
{
    private const KEY = 'wishlist';

    public function ids(): array
    {
        return array_values(array_unique(array_map('intval', session(self::KEY, []))));
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    /** @return bool true when the product is now in the wishlist */
    public function toggle(int $productId): bool
    {
        $ids = $this->ids();
        $now = ! in_array($productId, $ids, true);
        $ids = $now ? [...$ids, $productId] : array_values(array_diff($ids, [$productId]));
        session([self::KEY => $ids]);

        return $now;
    }

    public function products(): Collection
    {
        return Product::active()->card()->with('category')->whereIn('id', $this->ids())->get();
    }
}
