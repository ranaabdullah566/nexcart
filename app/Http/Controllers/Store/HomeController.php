<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\Review;
use App\Support\BusinessProfile;
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
                    'brands' => ['brands' => Brand::where('is_active', true)->whereHas('products', fn ($q) => $q->active())->withCount(['products' => fn ($q) => $q->active()])->orderBy('name')->limit(max(1, (int) ($s->settings['limit'] ?? 12)))->get()],
                    'reviews' => ['reviewData' => $this->reviews($s)],
                    'blog' => ['articles' => Article::live()->with('media')->orderByDesc('published_at')->orderByDesc('id')->limit(max(1, min(6, (int) ($s->settings['limit'] ?? 3))))->get()],
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

    /** Real, approved reviews + the overall rating summary (empty when the module is off or nobody reviewed yet). */
    private function reviews(HomeSection $s): array
    {
        if (! BusinessProfile::hasModule('reviews')) {
            return ['list' => collect(), 'count' => 0];
        }
        $all = Review::where('status', 'approved');
        $count = (clone $all)->count();
        $dist = (clone $all)->selectRaw('rating, count(*) n')->groupBy('rating')->pluck('n', 'rating');
        $list = (clone $all)->where('rating', '>=', max(1, min(5, (int) ($s->settings['min_rating'] ?? 4))))
            ->with(['product' => fn ($q) => $q->select('id', 'name', 'slug', 'status', 'translations'), 'order:id,city'])
            ->latest('id')->limit(max(3, min(24, (int) ($s->settings['limit'] ?? 9))))->get();

        return ['list' => $list, 'count' => $count, 'avg' => $count ? round((float) (clone $all)->avg('rating'), 1) : 0,
            'dist' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($r) => [$r => (int) ($dist[$r] ?? 0)])->all()];
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
