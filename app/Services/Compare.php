<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/** Up to four products a shopper wants to see side by side (kept in the session). */
class Compare
{
    private const KEY = 'compare';

    public const MAX = 4;

    public function ids(): array
    {
        return array_values(array_unique(array_map('intval', session(self::KEY, []))));
    }

    public function has(int $id): bool
    {
        return in_array($id, $this->ids(), true);
    }

    /** @return array{added: bool, full: bool, count: int} */
    public function toggle(int $id): array
    {
        $ids = $this->ids();
        if (in_array($id, $ids, true)) {
            $ids = array_values(array_diff($ids, [$id]));
            session([self::KEY => $ids]);

            return ['added' => false, 'full' => false, 'count' => count($ids)];
        }
        if (count($ids) >= self::MAX) {
            return ['added' => false, 'full' => true, 'count' => count($ids)];
        }
        $ids[] = $id;
        session([self::KEY => $ids]);

        return ['added' => true, 'full' => false, 'count' => count($ids)];
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function products(): Collection
    {
        $ids = $this->ids();

        return Product::active()->card()->with(['category'])->whereIn('id', $ids)->get()->sortBy(fn ($p) => array_search($p->id, $ids))->values();
    }
}
