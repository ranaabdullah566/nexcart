@php
    $__a1 = (string) setting('theme.accent', '#22d3ee');
    $__a2 = (string) setting('theme.primary', '#8b5cf6');
    $__default = (string) setting('theme.mode', 'dark');
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $__vm = ['required' => __('This field is required.'), 'email' => __('Enter a valid email address.'), 'url' => __('Enter a valid URL.'), 'number' => __('Enter a number.'), 'integer' => __('Enter a whole number.'),
        'min' => __('Must be at least :min.'), 'max' => __('Must be at most :max.'), 'minlength' => __('Use at least :min characters.'), 'maxlength' => __('Use at most :max characters.'),
        'pattern' => __('Please match the requested format.'), 'confirmed' => __('The two values do not match.'), 'strong' => __('At least 10 characters, with letters and numbers.')];
@endphp
<meta name="nx-vmsg" content="{{ json_encode($__vm, JSON_UNESCAPED_UNICODE) }}">
@if (isset($errors) && $errors->any())<meta name="nx-errors" content="{{ json_encode($errors->getBag('default')->toArray(), JSON_UNESCAPED_UNICODE) }}">@endif
<meta name="csp-nonce" content="{{ csp_nonce() }}">
<meta name="theme-color" content="#070b16">
<meta name="nx-tz" content="{{ setting('store.timezone', config('app.timezone')) }}">
<meta name="nx-fmt" content="d M Y|12">
<link rel="icon" href="{{ brand_favicon() }}">

{{-- Apply the saved theme before first paint (no flash). "auto" follows the device. --}}
<script nonce="{{ csp_nonce() }}">
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
@php
    $__radius = in_array(setting('theme.radius'), ['0.25rem', '0.5rem', '1rem', '1.5rem'], true) ? setting('theme.radius') : null;
    $__font = setting('theme.font') === 'inter' ? "'Inter'" : null;
@endphp
@if ($__radius || $__font)
    <style>html:root[data-theme] { @if ($__radius) --radius: {{ $__radius }}; @endif @if ($__font) --font-display: {!! $__font !!}, ui-sans-serif, sans-serif; @endif }</style>
@endif
