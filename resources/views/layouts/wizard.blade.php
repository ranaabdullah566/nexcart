<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<head>
    @include('partials.head', ['storefront' => true])
    <title>@yield('title') · NexCart</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="{{ asset('assets/css/storefront.css') }}?v={{ @filemtime(public_path('assets/css/storefront.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/store-market.css') }}?v={{ @filemtime(public_path('assets/css/store-market.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/store-market-pages.css') }}?v={{ @filemtime(public_path('assets/css/store-market-pages.css')) }}" rel="stylesheet">
    @stack('head')
</head>
<body class="sf mk-wizard">
@php
    $step = (int) trim($__env->yieldContent('step', '1'));
    $steps = [1 => ['Server & database', 'hdd-network'], 2 => ['Store & owner', 'shop'], 3 => ['Launch', 'rocket-takeoff']];
@endphp
<div class="mk-wz-bar">
    <div class="sf-container d-flex align-items-center justify-content-between gap-3">
        <span class="sf-brand"><span class="sf-brand-logo"><img src="{{ brand_logo() }}" alt=""></span><span class="sf-brand-name">NexCart</span></span>
        <div class="d-flex align-items-center gap-2">
            @include('partials.lang-switcher')
            <button type="button" class="mk-ic" data-theme-toggle aria-label="{{ __('Toggle light/dark mode') }}"><i class="bi bi-sun-fill"></i></button>
        </div>
    </div>
</div>

<main class="sf-container py-3">
    <div class="mk-auth mk-wz">
        <aside class="mk-auth-aside mk-wz-aside">
            <h2>@yield('headline')</h2>
            <p>@yield('lead')</p>
            <ol class="mk-wz-steps" aria-label="{{ __('Progress') }}">
                @foreach ($steps as $n => [$label, $icon])
                    <li class="{{ $n < $step ? 'done' : ($n === $step ? 'now' : '') }}">
                        <span>@if ($n < $step)<i class="bi bi-check-lg"></i>@else<i class="bi bi-{{ $icon }}"></i>@endif</span>
                        <div><small>{{ __('Step :n', ['n' => $n]) }}</small><b>{{ __($label) }}</b></div>
                    </li>
                @endforeach
            </ol>
        </aside>
        <section class="mk-wz-main">@yield('content')</section>
    </div>
</main>

<script nonce="{{ csp_nonce() }}">
    document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
        function paint() { var i = b.querySelector('i'); if (i) i.className = 'bi ' + (document.documentElement.getAttribute('data-theme') === 'dark' ? 'bi-sun-fill' : 'bi-moon-stars-fill'); }
        paint();
        b.addEventListener('click', function () {
            var root = document.documentElement, next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next); root.setAttribute('data-bs-theme', next);
            try { localStorage.setItem('nx-theme', next); } catch (e) {}
            paint();
        });
    });
</script>
<script defer src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('assets/js/nx-validate.js') }}?v={{ @filemtime(public_path('assets/js/nx-validate.js')) }}"></script>
<script defer src="{{ asset('assets/js/store-market.js') }}?v={{ @filemtime(public_path('assets/js/store-market.js')) }}"></script>
@stack('scripts')
</body>
</html>
