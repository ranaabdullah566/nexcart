@extends('layouts.admin')
@section('title', __('Theme'))

@php
    $v = fn (string $k, $d = null) => old($k, setting($k, $d));
    $logo = setting('brand.logo');
@endphp
@section('content')
<x-page-header :title="__('Theme')" :eyebrow="__('Storefront')" :sub="__('Change colours, shape, fonts and logo — the preview updates as you go. Nothing is live until you save.')" />

<div class="row g-3">
    <div class="col-xl-5">
        <form method="POST" action="{{ route('admin.theme.update') }}" enctype="multipart/form-data" id="themeForm" data-no-ajax>
            @csrf @method('PUT')
            <div class="card mb-3"><div class="card-body">
                <h5 class="mb-3">{{ __('Colours & shape') }}</h5>
                <div class="row g-3">
                    <div class="col-6"><label class="form-label">{{ __('Primary colour') }}</label><input type="color" name="primary" value="{{ $v('theme.primary') }}" class="form-control form-control-color w-100" data-live="primary"></div>
                    <div class="col-6"><label class="form-label">{{ __('Accent colour') }}</label><input type="color" name="accent" value="{{ $v('theme.accent') }}" class="form-control form-control-color w-100" data-live="accent"></div>
                    <div class="col-6"><label class="form-label">{{ __('Default mode') }}</label>
                        <select name="mode" class="form-select" data-live="mode">@foreach (['dark' => 'Dark', 'light' => 'Light', 'auto' => 'Follow device'] as $k => $l)<option value="{{ $k }}" @selected($v('theme.mode') === $k)>{{ __($l) }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label">{{ __('Corner roundness') }}</label>
                        <select name="radius" class="form-select" data-live="radius">@foreach ($radii as $k => $l)<option value="{{ $k }}" @selected($v('theme.radius', '1rem') === $k)>{{ __($l) }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">{{ __('Heading font') }}</label>
                        <select name="font" class="form-select" data-live="font"><option value="space" @selected($v('theme.font', 'space') === 'space')>Space Grotesk — {{ __('futuristic') }}</option><option value="inter" @selected($v('theme.font') === 'inter')>Inter — {{ __('clean') }}</option></select></div>
                </div>
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h5 class="mb-3">{{ __('Logo') }}</h5>
                @if ($logo)<div class="d-flex align-items-center gap-3 mb-2"><img src="{{ brand_logo() }}" alt="" style="height:48px;border-radius:12px"><div class="form-check"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rmLogo"><label class="form-check-label" for="rmLogo">{{ __('Remove logo') }}</label></div></div>@endif
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control @error('logo') is-invalid @enderror" data-live="logo">@error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">{{ __('PNG, JPG or WebP, square works best, up to 2 MB.') }}</div>
                <label class="form-label mt-3">{{ __('Browser icon (favicon)') }}</label>
                <input type="file" name="favicon" accept="image/png,image/jpeg,image/webp,image/x-icon" class="form-control @error('favicon') is-invalid @enderror">@error('favicon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if (setting('brand.favicon'))<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_favicon" value="1" id="rmFav"><label class="form-check-label" for="rmFav">{{ __('Remove icon') }}</label></div>@endif
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h5 class="mb-3">{{ __('Announcement bar') }}</h5>
                <div class="form-check form-switch mb-3"><input type="checkbox" role="switch" class="form-check-input" name="announce_enabled" value="1" id="annOn" @checked(old('announce_enabled', setting('announce.enabled'))) data-live="annOn"><label class="form-check-label" for="annOn">{{ __('Show a bar above the header') }}</label></div>
                <input name="announce_text" maxlength="160" value="{{ $v('announce.text') }}" class="form-control mb-2" placeholder="{{ __('Free delivery over Rs 3,000!') }}" data-live="annText">
                <input name="announce_url" value="{{ $v('announce.url') }}" class="form-control figure mb-2 @error('announce_url') is-invalid @enderror" placeholder="/shop">@error('announce_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @foreach ($locales as $code => $m)<input name="tr[{{ $code }}][announce_text]" maxlength="160" dir="{{ $m['dir'] }}" value="{{ old("tr.$code.announce_text", setting('announce.text_'.$code)) }}" class="form-control form-control-sm mb-2" placeholder="{{ __('Text') }} · {{ $m['label'] }}">@endforeach
            </div></div>

            <div class="d-flex justify-content-end"><button class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save theme') }}</button></div>
        </form>
    </div>

    <div class="col-xl-7">
        <div class="card" style="position:sticky;top:88px"><div class="card-body p-2">
            <div class="d-flex justify-content-between align-items-center px-2 py-1">
                <span class="small text-muted"><i class="bi bi-eye me-1"></i>{{ __('Live preview') }}</span>
                <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Preview size') }}">
                    <button type="button" class="btn btn-outline-secondary active" data-w="100%"><i class="bi bi-display"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-w="390px"><i class="bi bi-phone"></i></button>
                </div>
            </div>
            <div style="background:var(--surface);border-radius:14px;overflow:hidden;text-align:center">
                <iframe id="preview" src="{{ route('store.home') }}" title="{{ __('Storefront preview') }}" style="width:100%;height:calc(100vh - 220px);min-height:520px;border:0;transition:width .25s;background:var(--bg)"></iframe>
            </div>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    var frame = document.getElementById('preview'), form = document.getElementById('themeForm');
    var fonts = { space: "'Space Grotesk'", inter: "'Inter'" };
    function apply() {
        var d = frame.contentDocument; if (!d || !d.documentElement) return;
        var r = d.documentElement, f = new FormData(form);
        r.style.setProperty('--acc-2', f.get('primary')); r.style.setProperty('--acc-1', f.get('accent'));
        r.style.setProperty('--radius', f.get('radius'));
        r.style.setProperty('--font-display', fonts[f.get('font')] + ', ui-sans-serif, sans-serif');
        var mode = f.get('mode'); if (mode === 'auto') mode = matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        r.setAttribute('data-theme', mode); r.setAttribute('data-bs-theme', mode);
        var bar = d.getElementById('sfAnnounce'), on = form.querySelector('[name=announce_enabled]').checked, text = f.get('announce_text');
        if (on && text) {
            if (!bar) { bar = d.createElement('div'); bar.id = 'sfAnnounce'; bar.className = 'sf-announce'; d.body.insertBefore(bar, d.body.firstChild); }
            bar.textContent = text; bar.style.display = '';
        } else if (bar) { bar.style.display = 'none'; }
        var logo = form.querySelector('[name=logo]').files[0];
        if (logo) { var u = URL.createObjectURL(logo); d.querySelectorAll('.sf-brand-logo img').forEach(function (i) { i.src = u; }); }
    }
    form.addEventListener('input', apply); form.addEventListener('change', apply); frame.addEventListener('load', apply);
    document.querySelectorAll('[data-w]').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('[data-w]').forEach(function (x) { x.classList.toggle('active', x === b); });
            frame.style.width = b.dataset.w; frame.style.margin = '0 auto'; frame.style.display = 'block';
        });
    });
})();
</script>
@endpush
