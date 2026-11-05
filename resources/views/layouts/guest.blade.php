<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<head>
    @include('partials.head')
    <title>@yield('title') · {{ setting('store.name', config('app.name')) }}</title>
</head>
<body>
<div class="nx-guest">
    <div class="nx-guest-bar">
        @include('partials.lang-switcher')
        <button type="button" class="nx-icon-btn" id="nxThemeBtn" title="{{ __('Toggle light/dark mode') }}" aria-label="{{ __('Toggle light/dark mode') }}">
            <i class="bi bi-sun-fill" id="nxThemeIcon"></i>
        </button>
    </div>
    <div class="nx-guest-card @yield('card_class')">
        @yield('content')
    </div>
</div>
<script>
    (function () {
        var root = document.documentElement, icon = document.getElementById('nxThemeIcon');
        function paint() { icon.className = 'bi ' + (root.getAttribute('data-theme') === 'dark' ? 'bi-sun-fill' : 'bi-moon-stars-fill'); }
        paint();
        document.getElementById('nxThemeBtn').addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next); root.setAttribute('data-bs-theme', next);
            try { localStorage.setItem('nx-theme', next); } catch (e) {}
            paint();
        });
    })();
</script>
@include('partials.scripts')
</body>
</html>
