<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $sections = HomeSection::with('media')->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            ->map(function (HomeSection $s) {
                $data = match ($s->type) {
                    'categories' => ['categories' => $this->categories($s)],
                    'products' => ['products' => $this->products($s)],
                    'brands' => ['brands' => \App\Models\Brand::where('is_active', true)->whereHas('products', fn ($q) => $q->active())->withCount(['products' => fn ($q) => $q->active()])->orderBy('name')->limit(max(1, (int) ($s->settings['limit'] ?? 12)))->get()],
                    default => [],
                };

                return ['section' => $s] + $data;
            });

        $deals = Product::active()->card()->where('type', 'simple')->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price')
            ->orderByRaw('(compare_price - price) / compare_price desc')->limit((int) sf('home.hero_deals_count'))->get();
        if ($deals->isEmpty()) {
            $deals = Product::active()->card()->where('is_featured', true)->latest('id')->limit((int) sf('home.hero_deals_count'))->get();
        }
        if (! sf('home.hero_deals')) {
            $deals = collect();
        }

        return view('store.home', ['sections' => $sections, 'deals' => $deals]);
    }

    private function categories(HomeSection $s)
    {
        $ids = array_filter((array) ($s->settings['category_ids'] ?? []));

        return Category::withCount(['products' => fn ($q) => $q->active()])->where('is_active', true)
            ->when($ids, fn ($q) => $q->whereIn('id', $ids), fn ($q) => $q->whereNull('parent_id'))
            ->orderBy('sort_order')->orderBy('name')->limit((int) ($s->settings['limit'] ?? 8))->get();
    }

    private function products(HomeSection $s)
    {
        $limit = max(1, min(24, (int) ($s->settings['limit'] ?? 8)));
        $q = Product::active()->card();

        match ($s->settings['source'] ?? 'new') {
            'featured' => $q->where('is_featured', true)->latest('id'),
            'category' => $q->when($s->settings['category_id'] ?? null, function ($q, $id) {
                $cat = Category::find($id);
                $q->whereIn('category_id', $cat ? $cat->descendantIds() : [0]);
            })->latest('id'),
            default => $q->latest('id'),
        };

        return $q->limit($limit)->get();
    }
}
