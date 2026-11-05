<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<head>
    @include('partials.head', ['storefront' => true])
    @php
        $storeName = setting('store.name', config('app.name'));
        $pageTitle = trim($__env->yieldContent('title'));
        $metaDesc = trim($__env->yieldContent('description')) ?: (setting('store.tagline') ?: $storeName);
        $ogImage = trim($__env->yieldContent('og_image')) ?: asset('assets/images/icon-512.png');
        $navCats = \App\Models\Category::where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->limit(8)->get();
        $wishCount = count(app(\App\Services\Wishlist::class)->ids());
        $cartData = app(\App\Services\CartService::class)->toArray();
        $terms = (array) setting('business.terms', []);
        $headerMenu = \App\Models\MenuItem::resolved('header');
        $footerMenu = \App\Models\MenuItem::resolved('footer');
    @endphp
    <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ $storeName }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($metaDesc), 160) }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="{{ trim($__env->yieldContent('og_type')) ?: 'website' }}">
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:title" content="{{ $pageTitle ?: $storeName }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($metaDesc), 200) }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="manifest" href="{{ route('store.manifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/icon-192.png') }}">
    <link href="{{ asset('assets/css/storefront.css') }}?v={{ @filemtime(public_path('assets/css/storefront.css')) }}" rel="stylesheet">
    <script>document.documentElement.classList.add('sf-js')</script>
    @stack('head')
</head>
<body class="sf" data-page="{{ $page ?? '' }}">
<a href="#main" class="visually-hidden-focusable position-absolute p-2" style="z-index:2000">{{ __('Skip to content') }}</a>

@if ($ann = announcement())
    <div class="sf-announce" id="sfAnnounce">@if ($ann['url'])<a href="{{ $ann['url'] }}">{{ $ann['text'] }}</a>@else<span>{{ $ann['text'] }}</span>@endif</div>
@endif
<header class="sf-header">
    <div class="sf-container">
        <div class="sf-header-row">
            <a href="{{ route('store.home') }}" class="sf-brand" aria-label="{{ $storeName }}">
                <span class="sf-brand-logo"><img src="{{ brand_logo() }}" alt=""></span>
                <span class="sf-brand-name">{{ $storeName }}</span>
            </a>
            <nav class="sf-nav" aria-label="Main">
                <a href="{{ route('store.home') }}" @class(['active' => request()->routeIs('store.home')])>{{ __('Home') }}</a>
                <a href="{{ route('store.shop') }}" @class(['active' => request()->routeIs('store.shop')])>{{ __('Shop') }}</a>
                @foreach ($headerMenu as $m)<a href="{{ $m['href'] }}">{{ $m['label'] }}</a>@endforeach
                @if ($navCats->isNotEmpty())
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="padding:.4rem .8rem">{{ __('Categories') }}</a>
                        <ul class="dropdown-menu" style="background:var(--surface-solid);border:1px solid var(--border-strong);border-radius:14px;padding:.35rem">
                            @foreach ($navCats as $c)<li><a class="dropdown-item" style="border-radius:10px;color:var(--text)" href="{{ route('store.category', $c->slug) }}">{{ $c->trans('name') }}</a></li>@endforeach
                        </ul>
                    </div>
                @endif
            </nav>

            <form class="sf-search" action="{{ route('store.shop') }}" role="search" autocomplete="off">
                <i class="bi bi-search"></i>
                <input id="sfSearch" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search products…') }}" aria-label="{{ __('Search products…') }}">
                <div class="sf-suggest" id="sfSuggest" role="listbox"></div>
            </form>

            <div class="sf-actions">
                <div class="dropdown sf-lang">
                    <button class="sf-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Language') }}"><i class="bi bi-translate"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach (\App\Support\Locales::enabled() as $code => $meta)
                            <li><a class="dropdown-item @if (app()->getLocale() === $code) active @endif" href="{{ route('locale', $code) }}" lang="{{ str_replace('_', '-', $code) }}">{{ $meta['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="sf-icon-btn" data-theme-toggle aria-label="{{ __('Toggle light/dark mode') }}"><i class="bi bi-sun-fill"></i></button>
                <a href="{{ auth()->check() ? route('store.account') : route('login') }}" class="sf-icon-btn d-none d-sm-grid" aria-label="{{ __('Account') }}"><i class="bi bi-person"></i></a>
                <a href="{{ route('store.wishlist') }}" class="sf-icon-btn d-none d-sm-grid" aria-label="{{ __('Wishlist') }}"><i class="bi bi-heart"></i><span class="sf-badge" data-wish-count @if (! $wishCount) hidden @endif>{{ $wishCount }}</span></a>
                <a href="{{ route('store.cart') }}" class="sf-icon-btn" data-open-cart aria-label="{{ __('Cart') }}"><i class="bi bi-bag"></i><span class="sf-badge" data-cart-count @if (! $cartData['count']) hidden @endif>{{ $cartData['count'] }}</span></a>
            </div>
        </div>
    </div>
</header>

@if (session('notice'))<div class="sf-container pt-3"><div class="sf-alert" style="border-color:var(--acc-1)"><i class="bi bi-info-circle-fill fs-5" style="color:var(--acc-1)"></i>{{ session('notice') }}</div></div>@endif
<main id="main">@yield('content')</main>

<footer class="sf-footer">
    <div class="sf-container">
        <div class="row g-4">
            <div class="col-lg-5">
                <a href="{{ route('store.home') }}" class="sf-brand mb-3"><span class="sf-brand-logo"><img src="{{ brand_logo() }}" alt=""></span><span class="sf-brand-name">{{ $storeName }}</span></a>
                @if (setting('store.tagline'))<p class="text-muted" dir="auto">{{ setting('store.tagline') }}</p>@endif
            </div>
            <div class="col-6 col-lg-3">
                <h6>{{ __('Shop') }}</h6>
                <a href="{{ route('store.shop') }}">{{ __('All products') }}</a>
                @foreach ($navCats->take(4) as $c)<a href="{{ route('store.category', $c->slug) }}">{{ $c->trans('name') }}</a>@endforeach
                <a href="{{ route('store.wishlist') }}">{{ __('Wishlist') }}</a>
                <a href="{{ route('store.track') }}">{{ __('Track an order') }}</a>
                @foreach ($footerMenu as $m)<a href="{{ $m['href'] }}">{{ $m['label'] }}</a>@endforeach
            </div>
            <div class="col-6 col-lg-4">
                <h6>{{ __('Contact') }}</h6>
                @if (setting('store.email'))<a href="mailto:{{ setting('store.email') }}" dir="ltr" class="text-start"><i class="bi bi-envelope me-2"></i>{{ setting('store.email') }}</a>@endif
                @if (setting('store.phone'))<a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('store.phone')) }}" dir="ltr" class="text-start"><i class="bi bi-telephone me-2"></i>{{ setting('store.phone') }}</a>@endif
            </div>
        </div>
        <hr class="my-4">
        <div class="small text-muted">© {{ date('Y') }} {{ $storeName }}</div>
    </div>
</footer>

{{-- mobile bottom bar --}}
<nav class="sf-bottom" aria-label="Quick">
    <a href="{{ route('store.home') }}" @class(['on' => request()->routeIs('store.home')])><i class="bi bi-house-door"></i>{{ __('Home') }}</a>
    <a href="{{ route('store.shop') }}" @class(['on' => request()->routeIs('store.shop', 'store.category')])><i class="bi bi-grid"></i>{{ __('Shop') }}</a>
    <a href="{{ route('store.wishlist') }}" @class(['on' => request()->routeIs('store.wishlist')])><i class="bi bi-heart"></i>{{ __('Wishlist') }}</a>
    <a href="{{ route('store.cart') }}" data-open-cart><i class="bi bi-bag"></i>{{ __('Cart') }}<span class="sf-badge" data-cart-count @if (! $cartData['count']) hidden @endif>{{ $cartData['count'] }}</span></a>
</nav>

{{-- cart drawer --}}
<div class="offcanvas offcanvas-end sf-drawer" tabindex="-1" id="sfDrawer" aria-labelledby="sfDrawerTitle">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sfDrawerTitle"><i class="bi bi-bag me-2"></i>{{ __('Your cart') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" id="sfDrawerBody"></div>
    <div class="p-3 border-top" id="sfDrawerFoot" hidden style="border-color:var(--border) !important">
        <div class="d-flex justify-content-between mb-3"><span class="text-muted">{{ __('Subtotal') }}</span><b class="figure" id="sfSubtotal"></b></div>
        <div class="d-grid gap-2">
            @if (Route::has('store.checkout'))<a href="{{ route('store.checkout') }}" class="btn btn-primary btn-lg"><i class="bi bi-lock-fill me-1"></i> {{ __('Checkout') }}</a>@endif
            <a href="{{ route('store.cart') }}" class="sf-btn-ghost justify-content-center">{{ __('View cart') }}</a>
        </div>
        <div class="small text-muted text-center mt-2">{{ __('Shipping and tax are calculated at checkout.') }}</div>
    </div>
</div>

{{-- quick view --}}
<div class="modal fade sf-quick" id="sfQuick" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
        <div class="modal-content"><div class="modal-body p-3 p-md-4" id="sfQuickBody"></div></div>
    </div>
</div>

<div class="nx-toasts" id="sfToasts" aria-live="polite"></div>

@php
    $jsT = [
            'added' => __('Added to cart'), 'cartEmpty' => __('Your cart is empty'), 'remove' => __('Remove'), 'error' => __('Something went wrong. Please try again.'),
            'lowStock' => __('Only :n left in stock.'), 'wishAdded' => __('Saved to wishlist'), 'wishRemoved' => __('Removed from wishlist'),
            'chooseOption' => __('Choose an option first.'), 'outOfStock' => __('Out of stock'), 'addToCart' => __('Add to cart'),
            'seeAll' => __('See all results'), 'noResults' => __('No products found'),
        ];
@endphp
<script>
    window.SF = {
        cart: @json($cartData),
        routes: { cart: @json(route('store.cart.json')), add: @json(route('store.cart.add')), cartBase: @json(url('/cart')), wishBase: @json(url('/wishlist')), suggest: @json(route('store.suggest')) },
        money: function (n) { return @json(setting('store.currency_symbol')) + ' ' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        t: @json($jsT),
    };
</script>
<script defer src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('assets/js/storefront.js') }}?v={{ @filemtime(public_path('assets/js/storefront.js')) }}"></script>
@stack('jsonld')
@stack('scripts')
</body>
</html>
