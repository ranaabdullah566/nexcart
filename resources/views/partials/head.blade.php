@php
    $__a1 = (string) setting('theme.accent', '#22d3ee');
    $__a2 = (string) setting('theme.primary', '#8b5cf6');
    $__default = (string) setting('theme.mode', 'dark');
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#070b16">
<meta name="nx-tz" content="{{ setting('store.timezone', config('app.timezone')) }}">
<meta name="nx-fmt" content="d M Y|12">
<link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon.svg') }}">

{{-- Apply the saved theme before first paint (no flash). "auto" follows the device. --}}
<script>
    (function () {
        try {
            var t = localStorage.getItem('nx-theme') || @json($__default);
            if (t === 'auto') t = matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', t);
            document.documentElement.setAttribute('data-bs-theme', t);
            if (localStorage.getItem('nx-collapsed') === '1') document.documentElement.classList.add('nx-collapsed');
        } catch (e) {}
    })();
</script>

<link href="{{ asset('assets/css/fonts.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/bootstrap-icons.css') }}" rel="stylesheet">
@empty($storefront)
    <link href="{{ asset('assets/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/responsive.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet">
@endempty
<link href="{{ asset('assets/css/theme.css') }}?v={{ @filemtime(public_path('assets/css/theme.css')) }}" rel="stylesheet">
<link href="{{ asset('assets/css/nexcart.css') }}?v={{ @filemtime(public_path('assets/css/nexcart.css')) }}" rel="stylesheet">

{{-- Brand colours chosen in Store settings → Appearance --}}
@if (preg_match('/^#[0-9a-f]{6}$/i', $__a1) && preg_match('/^#[0-9a-f]{6}$/i', $__a2))
    <style>html:root[data-theme] { --acc-1: {{ $__a1 }}; --acc-2: {{ $__a2 }}; }</style>
@endif
