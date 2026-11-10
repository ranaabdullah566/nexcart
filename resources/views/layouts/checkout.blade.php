<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<head>
    @include('partials.head', ['storefront' => true])
    @php
        $storeName = setting('store.name', config('app.name'));
        $pageTitle = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES);
        $footerMenu = \App\Models\MenuItem::resolved('footer');
    @endphp
    <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ $storeName }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="{{ asset('assets/css/storefront.css') }}?v={{ @filemtime(public_path('assets/css/storefront.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/store-market.css') }}?v={{ @filemtime(public_path('assets/css/store-market.css')) }}" rel="stylesheet">
    <script nonce="{{ csp_nonce() }}">document.documentElement.classList.add('sf-js')</script>
    <link href="{{ asset('assets/css/store-market-pages.css') }}?v={{ @filemtime(public_path('assets/css/store-market-pages.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/store-future.css') }}?v={{ @filemtime(public_path('assets/css/store-future.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/store-tq.css') }}?v={{ @filemtime(public_path('assets/css/store-tq.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/checkout.css') }}?v={{ @filemtime(public_path('assets/css/checkout.css')) }}" rel="stylesheet">
    @stack('head')
</head>
<body class="sf @if (sf('design.marketplace')) sf-tq @endif sf-ck" data-page="checkout">
<a href="#main" class="visually-hidden-focusable position-absolute p-2" style="z-index:2000">{{ __('Skip to content') }}</a>
<main id="main">@yield('content')</main>
<div class="nx-toasts" id="sfToasts" aria-live="polite">@include('partials.notify')</div>
<script defer src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('assets/js/nx-validate.js') }}?v={{ @filemtime(public_path('assets/js/nx-validate.js')) }}"></script>
@stack('scripts')
</body>
</html>
