<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<head>
    @include('partials.head', ['storefront' => true])
    @php
        $storeName = setting('store.name', config('app.name'));
        $pageTitle = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES);   // @section('x', $value) is already escaped once
        $metaDesc = html_entity_decode(trim($__env->yieldContent('description')), ENT_QUOTES) ?: (setting('store.tagline') ?: $storeName);
        $ogImage = trim($__env->yieldContent('og_image')) ?: asset('assets/images/icon-512.png');
        $cartData = app(\App\Services\CartService::class)->toArray(false);   // no suggestions in every page's HTML — the drawer fetches them
        $wishCount = count(app(\App\Services\Wishlist::class)->ids());
        $compareCount = count(app(\App\Services\Compare::class)->ids());
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
    <link href="{{ asset('assets/css/store-market.css') }}?v={{ @filemtime(public_path('assets/css/store-market.css')) }}" rel="stylesheet">
    <script nonce="{{ csp_nonce() }}">document.documentElement.classList.add('sf-js')</script>
    <link href="{{ asset('assets/css/store-market-pages.css') }}?v={{ @filemtime(public_path('assets/css/store-market-pages.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/store-future.css') }}?v={{ @filemtime(public_path('assets/css/store-future.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/store-tq.css') }}?v={{ @filemtime(public_path('assets/css/store-tq.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/cart-fx.css') }}?v={{ @filemtime(public_path('assets/css/cart-fx.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/shop-fx.css') }}?v={{ @filemtime(public_path('assets/css/shop-fx.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/shop-side.css') }}?v={{ @filemtime(public_path('assets/css/shop-side.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/footer-fx.css') }}?v={{ @filemtime(public_path('assets/css/footer-fx.css')) }}" rel="stylesheet">
    <style>.sf{--mk-ratio:{{ sf('cards.ratio') }};--mk-fit:{{ sf('cards.fit') }}}</style>
    @stack('head')
</head>
<body class="sf @if (sf('design.marketplace')) sf-tq @endif" data-page="{{ $page ?? '' }}">
<a href="#main" class="visually-hidden-focusable position-absolute p-2" style="z-index:2000">{{ __('Skip to content') }}</a>

@php
    $tree = \App\Support\Storefront::tree();
    $socials = \App\Support\Storefront::socials();
    $recommended = $tree->where('products_count', '>', 0)->sortByDesc('products_count')->take(6);
    $ann = announcement();
    $loc = app()->getLocale();
    $topLinks = \App\Models\MenuItem::resolved('topbar');
    $colShop = \App\Models\MenuItem::resolved('footer_shop');
    $colHelp = \App\Models\MenuItem::resolved('footer_help');
    $colCompany = \App\Models\MenuItem::resolved('footer_company');
    $cookieOn = sf('cookie.enabled');
@endphp
@if (sf('topbar.enabled'))
<div class="mk-topbar @if (sf('header.light') || sf('design.marketplace')) mk-hd-light @endif" id="sfAnnounce"><div class="sf-container">
    @if ($ann)@if ($ann['url'])<a href="{{ $ann['url'] }}">{{ $ann['text'] }}</a>@else<span>{{ $ann['text'] }}</span>@endif
    @elseif (filled(sft('topbar.text')))<span>{{ sft('topbar.text') }}</span>
    @elseif (setting('store.tagline'))<span dir="auto">{{ setting('store.tagline') }}</span>@else<span>{{ __('Welcome to :store', ['store' => $storeName]) }}</span>@endif
    @if (sf('topbar.show_links'))
        <span class="mk-top-links d-none d-md-flex">
            @forelse ($topLinks as $m)<a href="{{ $m['href'] }}">{{ $m['label'] }}</a>
            @empty
                @if (setting('store.phone'))<a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('store.phone')) }}" dir="ltr"><i class="bi bi-telephone"></i> {{ setting('store.phone') }}</a>@endif
            @endforelse
        </span>
    @endif
</div></div>
@endif

<header class="mk-header sf-header @if (sf('header.light') || sf('design.marketplace')) mk-hd-light @endif @unless (sf('header.sticky')) mk-static @endunless">
    <div class="sf-container mk-header-row">
        <button class="mk-menu-btn" type="button" data-mobile-open aria-controls="mkDrawer" aria-expanded="false" aria-label="{{ __('Open menu') }}"><i class="bi bi-list"></i></button>
        <a href="{{ route('store.home') }}" class="sf-brand mk-brand" aria-label="{{ $storeName }}">
            <span class="sf-brand-logo"><img src="{{ brand_logo() }}" alt=""></span>
            <span class="sf-brand-name">{{ $storeName }}</span>
        </a>
        <form class="mk-search sf-search" id="mkSearch" action="{{ route('store.shop') }}" role="search" aria-label="{{ __('Search products') }}" autocomplete="off">
            <i class="bi bi-search"></i>
            <input id="sfSearch" type="search" name="q" value="{{ request('q') }}" placeholder="{{ sft('header.search_placeholder') }}" aria-label="{{ __('Search products…') }}">
            <button type="submit" aria-label="{{ __('Search') }}"><span>{{ sft('header.search_button') }}</span></button>
            <div class="sf-suggest" id="sfSuggest" role="listbox"></div>
        </form>
        @if (sf('header.show_lang'))
        <div class="dropdown mk-lang">
            <button class="mk-ic" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Language') }}"><i class="bi bi-globe2"></i><span class="d-none d-xl-inline">{{ strtoupper(explode('_', $loc)[0]) }}@if (str_contains($loc, '_')) · {{ str_replace('_', '-', explode('_', $loc)[1]) }}@endif</span></button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach (\App\Support\Locales::enabled() as $code => $meta)
                    <li><a class="dropdown-item @if ($loc === $code) active @endif" href="{{ route('locale', $code) }}" lang="{{ str_replace('_', '-', $code) }}">{{ $meta['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
        @endif
        <div class="mk-icons">
            @if (sf('header.show_shop'))<a class="mk-ic d-none d-md-flex" href="{{ route('store.shop') }}"><i class="bi bi-shop"></i><span>{{ __('Shop') }}</span></a>@endif
            @if (sf('header.show_saved'))<a class="mk-ic d-none d-md-flex" href="{{ route('store.wishlist') }}" aria-label="{{ __('Saved') }}"><i class="bi bi-heart"></i><span>{{ __('Saved') }}</span><b class="mk-count" data-wish-count @if (! $wishCount) hidden @endif>{{ $wishCount }}</b></a>@endif
            @if (sf('header.show_compare'))<a class="mk-ic d-none d-md-flex" href="{{ route('store.compare') }}" aria-label="{{ __('Compare') }}"><i class="bi bi-arrow-left-right"></i><span>{{ __('Compare') }}</span><b class="mk-count mk-count-blue" data-compare-count @if (! $compareCount) hidden @endif>{{ $compareCount }}</b></a>@endif
            <a class="mk-ic" href="{{ route('store.cart') }}" data-open-cart aria-label="{{ __('Cart') }}"><i class="bi bi-bag"></i><span class="d-none d-md-inline">{{ __('Basket') }}</span><b class="mk-count" data-cart-count @if (! $cartData['count']) hidden @endif>{{ $cartData['count'] }}</b></a>
            <button class="mk-ic mk-search-toggle" type="button" aria-controls="mkSearch" aria-expanded="false" aria-label="{{ __('Search') }}"><i class="bi bi-search"></i></button>
            @if (sf('header.show_theme') && ! sf('design.marketplace'))<button type="button" class="mk-ic" data-theme-toggle aria-label="{{ __('Toggle light/dark mode') }}"><i class="bi bi-sun-fill"></i></button>@endif
            @auth
                <div class="dropdown mk-acct">
                    <button class="mk-ic" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('My account') }}"><i class="bi bi-person-circle"></i><span class="d-none d-md-inline">{{ \Illuminate\Support\Str::limit(auth()->user()->name, 12) }}</span></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('store.account') }}"><i class="bi bi-person me-2"></i>{{ __('My account') }}</a></li>
                        @if (auth()->user()->isStaff())<li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>{{ __('Admin panel') }}</a></li>@endif
                        <li><form method="POST" action="{{ route('logout') }}" data-no-validate>@csrf<button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>{{ __('Sign out') }}</button></form></li>
                    </ul>
                </div>
            @else
                <a class="mk-ic" href="{{ route('login') }}" aria-label="{{ __('Sign in') }}"><i class="bi bi-person"></i><span class="d-none d-md-inline">{{ __('Sign in') }}</span></a>
            @endauth
        </div>
    </div>
</header>

<nav class="mk-cats @if (sf('header.light') || sf('design.marketplace')) mk-hd-light @endif" aria-label="{{ __('Store navigation') }}">
    <div class="sf-container mk-cats-row">
        @if (sf('header.show_mega'))
        <div class="mk-mega" data-mega>
            <button class="mk-mega-btn" type="button" aria-expanded="false" aria-controls="mkMega"><i class="bi bi-list"></i> {{ sft('header.mega_label') }} <i class="bi bi-chevron-down mk-caret"></i></button>
            <div class="mk-mega-panel" id="mkMega">
                @if ($tree->isEmpty())
                    <div class="p-4 text-muted">{{ __('No categories yet.') }}</div>
                @else
                    @if (sf('header.mega_recommended'))<div class="mk-mega-rec">
                        <div class="mk-mega-title"><div><b>{{ sft('header.mega_rec_title') }}</b><small>{{ sft('header.mega_rec_sub') }}</small></div><a href="{{ route('store.shop') }}">{{ __('Browse everything') }} →</a></div>
                        <div class="mk-rec-grid">@foreach ($recommended as $c)<a href="{{ route('store.category', $c->slug) }}"><span class="mk-mark"><i class="bi bi-{{ \App\Support\Storefront::categoryIcon($c->name) }}"></i></span><b>{{ $c->trans('name') }}</b></a>@endforeach</div>
                    </div>@endif
                    <div class="mk-mega-all-head">
                        <b>{{ __('All categories') }} <small>{{ $tree->count() }}</small></b>
                        <label class="mk-mega-find"><i class="bi bi-search"></i><input type="search" data-cat-filter placeholder="{{ __('Find a category…') }}" aria-label="{{ __('Find a category…') }}" autocomplete="off" maxlength="40"></label>
                    </div>
                    <div class="mk-mega-all" data-cat-list>
                        @foreach ($tree as $c)<a href="{{ route('store.category', $c->slug) }}" data-name="{{ mb_strtolower($c->name.' '.$c->trans('name')) }}"><span class="mk-mark sm"><i class="bi bi-{{ \App\Support\Storefront::categoryIcon($c->name) }}"></i></span><b>{{ $c->trans('name') }}</b><small>{{ $c->products_count }}</small></a>@endforeach
                        <p class="mk-mega-none" hidden>{{ __('No category matches.') }}</p>
                    </div>
                @endif
            </div>
        </div>
        <span class="mk-cats-sep" aria-hidden="true"></span>
        @endif
        @if (sf('header.show_deals'))<a href="{{ route('store.shop', ['sort' => 'discount']) }}" class="hot"><i class="bi bi-lightning-charge-fill"></i> {{ sft('header.deals_label') }}</a>@endif
        @if (sf('header.show_shop_link'))<a href="{{ route('store.shop') }}" @class(['on' => request()->routeIs('store.shop')])>{{ __('Shop') }}</a>@endif
        @if (sf('header.show_blog') && \App\Models\Article::live()->exists())<a href="{{ route('store.blog') }}" @class(['on' => request()->routeIs('store.blog', 'store.article')])>{{ sft('header.blog_label') }}</a>@endif
        @foreach ($headerMenu as $m)<a href="{{ $m['href'] }}">{{ $m['label'] }}</a>@endforeach
        @if (sf('header.show_track'))<a href="{{ route('store.track') }}" class="mk-push">{{ sft('header.track_label') }}</a>@endif
        @if (sf('header.show_location'))<button type="button" class="fx-loc" data-fx-loc aria-haspopup="dialog"><i class="bi bi-geo-alt-fill"></i><span><small>{{ __('Deliver to') }}</small><b data-fx-loc-name>{{ __('Choose area') }}</b></span><i class="bi bi-chevron-down"></i></button>@endif
        @if (sf('header.show_rx') && \App\Support\BusinessProfile::hasModule('prescriptions'))<a href="{{ route('store.prescription.upload') }}" class="fx-rx-btn"><i class="bi bi-camera-fill"></i> {{ sft('header.rx_label') }}</a>@endif
    </div>
</nav>

{{-- mobile drawer --}}
<div class="mk-drawer-layer" data-mobile-nav hidden>
    <button class="mk-drawer-backdrop" type="button" data-mobile-close aria-label="{{ __('Close menu') }}"></button>
    <aside class="mk-drawer" id="mkDrawer" role="dialog" aria-modal="true" aria-label="{{ __('Menu') }}" tabindex="-1">
        <header><div><small>{{ __('Explore') }} {{ $storeName }}</small><b>{{ __('Store navigation') }}</b></div><button type="button" data-mobile-close aria-label="{{ __('Close menu') }}"><i class="bi bi-x-lg"></i></button></header>
        <div class="mk-drawer-tabs" role="tablist"><button type="button" role="tab" class="on" data-drawer-tab="menu"><i class="bi bi-list"></i> {{ __('Menu') }}</button><button type="button" role="tab" data-drawer-tab="cats"><i class="bi bi-grid"></i> {{ __('Categories') }}</button></div>
        <div class="mk-drawer-body">
            <section data-drawer-panel="menu">
                <nav class="mk-drawer-links">
                    <a href="{{ route('store.home') }}"><i class="bi bi-house-door"></i>{{ __('Home') }}<i class="bi bi-chevron-right"></i></a>
                    <a href="{{ route('store.shop') }}"><i class="bi bi-shop"></i>{{ __('Shop') }}<i class="bi bi-chevron-right"></i></a>
                    <a href="{{ route('store.shop', ['sort' => 'discount']) }}"><i class="bi bi-lightning-charge"></i>{{ sft('header.deals_label') }}<i class="bi bi-chevron-right"></i></a>
                    @if (sf('header.show_blog') && \App\Models\Article::live()->exists())<a href="{{ route('store.blog') }}"><i class="bi bi-journal-richtext"></i>{{ sft('header.blog_label') }}<i class="bi bi-chevron-right"></i></a>@endif
                    <a href="{{ route('store.wishlist') }}"><i class="bi bi-heart"></i>{{ __('Saved') }}<i class="bi bi-chevron-right"></i></a>
                    <a href="{{ route('store.compare') }}"><i class="bi bi-arrow-left-right"></i>{{ __('Compare') }}<i class="bi bi-chevron-right"></i></a>
                    <a href="{{ route('store.contact') }}"><i class="bi bi-chat-heart"></i>{{ __('Contact us') }}<i class="bi bi-chevron-right"></i></a>
                    @if (sf('header.show_rx') && \App\Support\BusinessProfile::hasModule('prescriptions'))<a href="{{ route('store.prescription.upload') }}"><i class="bi bi-camera-fill"></i>{{ sft('header.rx_label') }}<i class="bi bi-chevron-right"></i></a>@endif
                    @if (sf('header.show_location'))<a href="#" data-fx-loc><i class="bi bi-geo-alt-fill"></i>{{ __('Deliver to') }}: <b data-fx-loc-name>{{ __('Choose area') }}</b><i class="bi bi-chevron-right"></i></a>@endif
                    <a href="{{ route('store.track') }}"><i class="bi bi-truck"></i>{{ sft('header.track_label') }}<i class="bi bi-chevron-right"></i></a>
                    @foreach ($headerMenu as $m)<a href="{{ $m['href'] }}"><i class="bi bi-link-45deg"></i>{{ $m['label'] }}<i class="bi bi-chevron-right"></i></a>@endforeach
                </nav>
                <div class="mk-drawer-lang"><small>{{ __('Language') }}</small><div>@foreach (\App\Support\Locales::enabled() as $code => $meta)<a href="{{ route('locale', $code) }}" class="{{ $loc === $code ? 'on' : '' }}">{{ $meta['label'] }}</a>@endforeach</div></div>
            </section>
            <section data-drawer-panel="cats" hidden>
                <a class="mk-drawer-all" href="{{ route('store.shop') }}">{{ __('Browse everything') }} <i class="bi bi-arrow-right"></i></a>
                <label class="mk-mega-find mk-drawer-find"><i class="bi bi-search"></i><input type="search" data-cat-filter placeholder="{{ __('Find a category…') }}" aria-label="{{ __('Find a category…') }}" autocomplete="off" maxlength="40"></label>
                <div class="mk-drawer-flat" data-cat-list>
                    @foreach ($tree as $c)<a href="{{ route('store.category', $c->slug) }}" data-name="{{ mb_strtolower($c->name.' '.$c->trans('name')) }}"><span class="mk-mark sm"><i class="bi bi-{{ \App\Support\Storefront::categoryIcon($c->name) }}"></i></span><b>{{ $c->trans('name') }}</b><small>{{ $c->products_count }}</small></a>@endforeach
                    <p class="mk-mega-none" hidden>{{ __('No category matches.') }}</p>
                </div>
            </section>
        </div>
    </aside>
</div>

<main id="main">@yield('content')</main>

<footer class="mk-footer ft">
    @if (sf('footer.show_newsletter'))
    <div class="ft-news"><div class="sf-container">
        <form method="POST" action="{{ route('store.subscribe') }}" class="ft-news-form" data-no-ajax>
            @csrf
            <b><i class="bi bi-envelope-heart"></i> {{ sft('footer.newsletter_title') }}</b>
            <div class="ft-news-in"><input type="email" name="email" required maxlength="190" autocomplete="email" placeholder="{{ __('Your email') }}" aria-label="{{ __('Your email') }}"><button type="submit" aria-label="{{ __('Subscribe') }}"><span>{{ __('Subscribe') }}</span><i class="bi bi-arrow-right"></i></button></div>
            <x-honeypot />
        </form>
    </div></div>
    @endif
    @php
        $mapUrl = sf('footer.show_map') ? \App\Support\Storefront::mapUrl() : null;
        $mapQ = sf('footer.show_map') ? \App\Support\Storefront::mapQuery() : null;
        $showMap = sf('footer.show_map') && ($mapUrl || $mapQ);
        $fPhone = sf('footer.show_contact') && sf('footer.show_phone') ? setting('store.phone') : null;
        $fMail = sf('footer.show_contact') && sf('footer.show_email') ? setting('store.email') : null;
        $fAddr = sf('footer.show_contact') && sf('footer.show_address') ? setting('store.address') : null;
        $fHours = sf('footer.show_contact') && sf('footer.show_hours') ? setting('store.hours') : null;
        $fCats = sf('footer.show_categories') ? $tree->sortByDesc('products_count')->take((int) sf('footer.categories_count')) : collect();
        $about = sft('footer.about') ?: setting('store.tagline');
    @endphp
    <div class="sf-container ft-main">
        <div class="ft-grid @if ($showMap) has-map @endif">
            <div class="ft-brand">
                <a href="{{ route('store.home') }}" class="sf-brand ft-logo"><span class="sf-brand-logo"><img src="{{ brand_logo() }}" alt=""></span><span class="sf-brand-name">{{ $storeName }}</span></a>
                @if ($about)<p dir="auto">{{ $about }}</p>@endif
                @if ($fPhone || $fMail || $fAddr || $fHours)
                <ul class="ft-contact">
                    @if ($fPhone)<li><i class="bi bi-telephone-fill"></i><a href="tel:{{ preg_replace('/[^0-9+]/', '', $fPhone) }}" dir="ltr">{{ $fPhone }}</a></li>@endif
                    @if ($fMail)<li><i class="bi bi-envelope-fill"></i><a href="mailto:{{ $fMail }}" dir="ltr">{{ $fMail }}</a></li>@endif
                    @if ($fAddr)<li><i class="bi bi-geo-alt-fill"></i><span dir="auto">{{ $fAddr }}</span></li>@endif
                    @if ($fHours)<li><i class="bi bi-clock-fill"></i><span dir="auto">{{ $fHours }}</span></li>@endif
                </ul>
                @endif
                @if ($socials && sf('footer.show_socials'))<div class="ft-socials" aria-label="{{ __('Follow us') }}">@foreach ($socials as $so)<a href="{{ $so['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $so['label'] }}" title="{{ $so['label'] }}"><i class="bi bi-{{ $so['icon'] }}"></i></a>@endforeach</div>@endif
            </div>
            @if ($fCats->isNotEmpty())
            <nav class="ft-col" aria-label="{{ sft('footer.categories_title') }}"><div class="ft-h">{{ sft('footer.categories_title') }}</div>
                @foreach ($fCats as $c)<a href="{{ route('store.category', $c->slug) }}">{{ $c->trans('name') }}</a>@endforeach
                <a class="ft-all" href="{{ route('store.shop') }}">{{ __('All categories') }} <i class="bi bi-arrow-right"></i></a>
            </nav>
            @endif
            <nav class="ft-col" aria-label="{{ sft('footer.shop_title') }}"><div class="ft-h">{{ sft('footer.shop_title') }}</div>
                @forelse ($colShop as $m)<a href="{{ $m['href'] }}">{{ $m['label'] }}</a>
                @empty
                    <a href="{{ route('store.shop') }}">{{ __('All products') }}</a>
                    <a href="{{ route('store.shop', ['sort' => 'discount']) }}">{{ sft('header.deals_label') }}</a>
                @endforelse
            </nav>
            <nav class="ft-col" aria-label="{{ sft('footer.help_title') }}"><div class="ft-h">{{ sft('footer.help_title') }}</div>
                @forelse ($colHelp as $m)<a href="{{ $m['href'] }}">{{ $m['label'] }}</a>
                @empty
                    <a href="{{ route('store.track') }}">{{ sft('header.track_label') }}</a>
                    <a href="{{ route('store.contact') }}">{{ __('Contact us') }}</a>
                    <a href="{{ auth()->check() ? route('store.account') : route('login') }}">{{ __('My account') }}</a>
                @endforelse
            </nav>
            <nav class="ft-col" aria-label="{{ sft('footer.company_title') }}"><div class="ft-h">{{ sft('footer.company_title') }}</div>
                @forelse ($colCompany as $m)<a href="{{ $m['href'] }}">{{ $m['label'] }}</a>@empty<a href="{{ route('store.home') }}">{{ $storeName }}</a>@endforelse
            </nav>
            @if ($showMap)
            <div class="ft-map">
                <div class="ft-h">{{ sft('footer.map_title') }}</div>
                <div class="ft-map-box">
                    @if ($mapQ)<iframe title="{{ sft('footer.map_title') }}" src="https://www.google.com/maps?q={{ rawurlencode($mapQ) }}&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    @else<div class="ft-map-ph"><i class="bi bi-geo-alt-fill"></i></div>@endif
                </div>
                @if ($mapUrl)<a class="ft-dir" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-sign-turn-right-fill"></i> {{ __('Get directions') }}</a>@endif
            </div>
            @endif
        </div>
        <div class="ft-bot">
            <span>{{ sft('footer.copyright') ?: '© '.date('Y').' '.$storeName.'. '.__('All rights reserved.') }}</span>
            <nav class="ft-legal" aria-label="{{ __('Legal') }}">
                @if (sf('footer.show_legal'))@foreach ($footerMenu->take(6) as $m)<a href="{{ $m['href'] }}">{{ $m['label'] }}</a>@endforeach @endif
                @if (\App\Support\License::showBranding())<span>{{ __('Powered by NexCart') }}</span>@endif
            </nav>
        </div>
    </div>
</footer>

{{-- cookie choices --}}
@if ($cookieOn)
<section class="mk-cookie" id="mkCookie" hidden aria-label="{{ __('Cookie choices') }}">
    <div class="mk-cookie-mark" aria-hidden="true"><i class="bi bi-shield-check"></i></div>
    <div><b>{{ sft('cookie.title') }}</b><p>{{ sft('cookie.body') }}</p></div>
    <div class="mk-cookie-actions"><button type="button" class="sf-btn-ghost" data-cookie-choice="essential">{{ sft('cookie.essential_label') }}</button><button type="button" class="btn btn-primary" data-cookie-choice="all">{{ sft('cookie.accept_label') }}</button></div>
</section>
@endif

@if (isset($socials['whatsapp']) && sf('float.whatsapp'))<a class="mk-wa" href="{{ $socials['whatsapp']['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>@endif
@if (sf('float.top'))<button class="mk-top" id="mkTop" type="button" aria-label="{{ __('Back to top') }}" hidden><i class="bi bi-arrow-up"></i></button>@endif

@if (sf('header.show_rx') && \App\Support\BusinessProfile::hasModule('prescriptions') && ! request()->routeIs('store.prescription.*', 'store.checkout*', 'store.cart'))
<a href="{{ route('store.prescription.upload') }}" class="fx-rx-fab"><i class="bi bi-camera-fill"></i> {{ sft('header.rx_label') }}</a>
@endif
{{-- mobile bottom bar --}}
<nav class="sf-bottom mk-bottom" aria-label="Quick">
    <a href="{{ route('store.home') }}" @class(['on' => request()->routeIs('store.home')])><i class="bi bi-house-door"></i>{{ __('Home') }}</a>
    <a href="{{ route('store.shop') }}" @class(['on' => request()->routeIs('store.shop', 'store.category')])><i class="bi bi-grid"></i>{{ __('Shop') }}</a>
    <a href="{{ route('store.cart') }}" data-open-cart><i class="bi bi-bag"></i>{{ __('Basket') }}<span class="sf-badge" data-cart-count @if (! $cartData['count']) hidden @endif>{{ $cartData['count'] }}</span></a>
    <a href="{{ auth()->check() ? route('store.account') : route('login') }}" @class(['on' => request()->routeIs('store.account', 'login')])><i class="bi bi-person"></i>{{ __('Account') }}</a>
</nav>

{{-- cart drawer --}}
<div class="offcanvas offcanvas-end sf-drawer sf-mc" tabindex="-1" id="sfDrawer" aria-labelledby="sfDrawerTitle">
    <div class="mc-glow" aria-hidden="true"></div>
    <header class="mc-head">
        <div class="mc-title"><span class="mc-ic"><i class="bi bi-bag-check"></i></span><div><h5 id="sfDrawerTitle">{{ __('Your cart') }}</h5><small id="mcCount">&nbsp;</small></div></div>
        <button type="button" class="mc-x" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"><i class="bi bi-x-lg"></i></button>
    </header>
    <div class="mc-ship" id="mcShip" hidden></div>
    <div class="mc-scroll" id="sfDrawerBody"></div>
    <footer class="mc-foot" id="sfDrawerFoot" hidden>
        <div class="mc-sum">
            <div class="mc-row mc-save" id="mcSaveRow" hidden><span><i class="bi bi-stars"></i> {{ __('You save') }}</span><b id="mcSave"></b></div>
            <div class="mc-row"><span>{{ __('Subtotal') }}</span><b class="mc-total" id="sfSubtotal"></b></div>
            <small class="mc-note">{{ __('Shipping and tax are calculated at checkout.') }}</small>
        </div>
        @if (Route::has('store.checkout'))<a href="{{ route('store.checkout') }}" class="mc-cta"><i class="bi bi-lock-fill"></i> {{ __('Checkout') }} <i class="bi bi-arrow-right mc-arrow"></i></a>@endif
        <a href="{{ route('store.cart') }}" class="mc-view">{{ __('View full cart') }}</a>
        <ul class="mc-trust"><li><i class="bi bi-shield-check"></i> {{ __('Secure checkout') }}</li><li><i class="bi bi-patch-check"></i> {{ __('Genuine medicines') }}</li><li><i class="bi bi-truck"></i> {{ __('Fast delivery') }}</li></ul>
    </footer>
</div>

{{-- quick view --}}
<div class="modal fade sf-quick" id="sfQuick" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
        <div class="modal-content"><div class="modal-body p-3 p-md-4" id="sfQuickBody"></div></div>
    </div>
</div>

<div class="nx-toasts" id="sfToasts" aria-live="polite">@include('partials.notify')</div>

@php
    $jsT = [
            'added' => __('Added to cart'), 'cartEmpty' => __('Your cart is empty'), 'remove' => __('Remove'), 'error' => __('Something went wrong. Please try again.'),
            'lowStock' => __('Only :n left in stock.'), 
            'chooseOption' => __('Choose an option first.'), 'outOfStock' => __('Out of stock'), 'addToCart' => __('Add to cart'),
            'seeAll' => __('See all results'), 'categories' => __('Categories'), 'medicines' => __('Medicines'), 'noResults' => __('No products found'),
            'item' => __('item'), 'items' => __('items'), 'freeDone' => __('You get free delivery!'), 'freeMore' => __('Add :amount more for free delivery', ['amount' => ':amount']),
            'cartEmptySub' => __('Looks like you have not added anything yet.'), 'startShopping' => __('Start shopping'), 'each' => __('each'), 'less' => __('Less'), 'more' => __('More'), 'complete' => __('Complete your order'), 'savedLater' => __('Saved for later'),
        ];
@endphp
@if (sf('header.show_location'))
<div class="fx-modal" id="fxLoc" role="dialog" aria-modal="true" aria-labelledby="fxLocT" hidden>
    <div class="fx-modal-box">
        <button type="button" class="fx-modal-x" data-fx-close aria-label="{{ __('Close') }}"><i class="bi bi-x-lg"></i></button>
        <span class="fx-modal-ic"><i class="bi bi-geo-alt-fill"></i></span>
        <h3 id="fxLocT">{{ __('Where should we deliver?') }}</h3>
        <p>{{ __('Pick your city so we can show delivery options and prefill checkout.') }}</p>
        <div class="fx-cities">@foreach (array_filter(array_map('trim', explode(',', sft('header.cities')))) as $c)<button type="button" data-fx-city-pick="{{ $c }}">{{ $c }}</button>@endforeach</div>
        <form class="fx-city-form" data-fx-city-form><input type="text" maxlength="60" placeholder="{{ __('Other city…') }}" aria-label="{{ __('Other city…') }}" class="form-control"><button class="btn btn-primary">{{ __('Select') }}</button></form>
    </div>
</div>
@endif
<script nonce="{{ csp_nonce() }}">
    window.SF = {
        cart: @json($cartData),
        routes: { shop: @json(route('store.shop')), cart: @json(route('store.cart.json')), add: @json(route('store.cart.add')), cartBase: @json(url('/cart')), suggest: @json(route('store.suggest')) },
        t2: { copied: @json(__('Link copied')) },
        money: function (n) { return @json(setting('store.currency_symbol')) + ' ' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        t: @json($jsT),
    };
</script>
<script defer src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('assets/js/nx-validate.js') }}?v={{ @filemtime(public_path('assets/js/nx-validate.js')) }}"></script>
<script defer src="{{ asset('assets/js/store-market.js') }}?v={{ @filemtime(public_path('assets/js/store-market.js')) }}"></script>
<script defer src="{{ asset('assets/js/storefront.js') }}?v={{ @filemtime(public_path('assets/js/storefront.js')) }}"></script>
<script defer src="{{ asset('assets/js/store-future.js') }}?v={{ @filemtime(public_path('assets/js/store-future.js')) }}"></script>
@stack('jsonld')
@stack('scripts')
</body>
</html>
