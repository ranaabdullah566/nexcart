<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect([['loc' => url('/'), 'mod' => null], ['loc' => route('store.shop'), 'mod' => null]])
            ->merge(Category::where('is_active', true)->get()->map(fn ($c) => ['loc' => route('store.category', $c->slug), 'mod' => $c->updated_at]))
            ->merge(Page::where('is_published', true)->get(['slug', 'updated_at'])->map(fn ($p) => ['loc' => route('store.page', $p->slug), 'mod' => $p->updated_at]))
            ->merge(Product::active()->get(['slug', 'updated_at'])->map(fn ($p) => ['loc' => route('store.product', $p->slug), 'mod' => $p->updated_at]));

        return response()->view('store.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $txt = "User-agent: *\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /account\nDisallow: /order\nDisallow: /track\nDisallow: /setup\nDisallow: /login\nSitemap: ".url('/sitemap.xml')."\n";

        return response($txt, 200, ['Content-Type' => 'text/plain']);
    }

    public function manifest(): JsonResponse
    {
        $json = [
            'name' => setting('store.name'),
            'short_name' => mb_substr((string) setting('store.name'), 0, 12),
            'description' => setting('store.tagline') ?: setting('store.name'),
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#070b16',
            'theme_color' => setting('theme.primary', '#8b5cf6'),
            'lang' => str_replace('_', '-', app()->getLocale()),
            'icons' => [
                ['src' => asset('assets/images/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => asset('assets/images/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ];

        return response()->json($json, 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
