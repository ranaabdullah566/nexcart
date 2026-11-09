<!DOCTYPE html>
@php
    // Error pages must never depend on the database: every lookup is guarded.
    $safe = function (callable $f, $fallback = null) { try { return $f(); } catch (\Throwable $e) { return $fallback; } };
    $brand = $safe(fn () => setting('store.name', config('app.name')), config('app.name'));
    $home = $safe(fn () => url('/'), '/');
    $dir = $safe(fn () => \App\Support\Locales::dir(), 'ltr');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir }}">
<head>
    @include('partials.head', ['storefront' => true])
    <title>@yield('code') · {{ $brand }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="{{ asset('assets/css/storefront.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/store-market.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/store-market-pages.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/store-future.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/store-tq.css') }}" rel="stylesheet">
    <style nonce="{{ csp_nonce() }}">
        .sf .fx-err { min-height: 100vh; display: grid; place-items: center; padding: 2rem 1rem; text-align: center; }
        .fx-err-code { font: 800 clamp(5rem, 22vw, 11rem)/1 var(--font-display, inherit); letter-spacing: -.06em; margin: 0; background: linear-gradient(110deg, var(--fx-a), var(--fx-b) 60%, var(--fx-a)); background-size: 200% auto; -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; animation: fxText 6s linear infinite alternate; filter: drop-shadow(0 10px 50px color-mix(in srgb, var(--fx-a) 45%, transparent)); }
        .fx-err h1 { font-size: clamp(1.4rem, 4vw, 2.1rem); margin: .2rem 0 .6rem; letter-spacing: -.02em; }
        .fx-err p { color: var(--text-2); max-width: 46ch; margin: 0 auto 1.6rem; }
        .fx-err-card { max-width: 640px; margin-inline: auto; padding: clamp(1.5rem, 5vw, 3rem); }
        .fx-err-pill { display: inline-grid; place-items: center; width: 64px; height: 64px; border-radius: 20px; font-size: 1.8rem; color: #fff; background: var(--fx-grad); box-shadow: 0 14px 40px -12px var(--fx-a); margin-bottom: 1rem; animation: fxFloatY 5s ease-in-out infinite; }
        .fx-err-btns { display: flex; gap: .7rem; justify-content: center; flex-wrap: wrap; }
    </style>
</head>
@php $__tq = $safe(fn () => (bool) sf('design.marketplace'), false); @endphp
<body class="sf @if ($__tq) sf-tq @endif">
<main class="fx-err">
    <div class="fx-err-card mk-box">
        <span class="fx-err-pill"><i class="bi bi-@yield('icon', 'exclamation-triangle')"></i></span>
        <p class="fx-err-code" aria-hidden="true">@yield('code')</p>
        <h1>@yield('headline')</h1>
        <p>@yield('message')</p>
        <div class="fx-err-btns">
            <a href="{{ $home }}" class="btn btn-primary btn-lg"><i class="bi bi-house-door me-1"></i> {{ __('Back to home') }}</a>
            @hasSection('extra')@yield('extra')@else<a href="javascript:history.back()" class="btn btn-outline-primary btn-lg"><i class="bi bi-arrow-left me-1"></i> {{ __('Go back') }}</a>@endif
        </div>
    </div>
</main>
</body>
</html>
