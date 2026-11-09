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
                <h2 class="mb-3 h5">{{ __('Colours & shape') }}</h2>
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


            <div class="card mb-3" id="designCard"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-1">
                    <h2 class="h5 mb-0">{{ __('Marketplace design') }}</h2>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="designResetBtn"><i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('Reset to default') }}</button>
                </div>
                <p class="text-muted small">{{ __('Colours, fonts and shapes of the white-and-navy storefront (and the admin panel). The preview updates as you go; nothing is live until you save.') }}</p>
                <input type="hidden" name="design_reset" value="0" id="designReset">

                <div class="mb-3">
                    <div class="form-label">{{ __('Ready-made colour sets') }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (\App\Support\Design::PRESETS as $pk => [$plabel, $pvals])
                            <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2" data-preset='@json($pvals)'>
                                <span class="d-inline-flex"><i style="width:14px;height:14px;background:{{ $pvals['navy'] }};border-radius:3px 0 0 3px"></i><i style="width:14px;height:14px;background:{{ $pvals['blue'] }}"></i><i style="width:14px;height:14px;background:{{ $pvals['orange'] }};border-radius:0 3px 3px 0"></i></span>{{ __($plabel) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="row g-3">
                    @foreach (\App\Support\Design::COLORS as $ck => [$clabel, $cdef, $chelp])
                        <div class="col-6">
                            <label class="form-label" for="dz_{{ $ck }}">{{ __($clabel) }}</label>
                            <div class="input-group">
                                <input type="color" id="dz_{{ $ck }}" name="design[{{ $ck }}]" value="{{ old("design.$ck", $design[$ck]) }}" class="form-control form-control-color" data-design="{{ $ck }}" data-default="{{ $cdef }}" style="max-width:56px">
                                <input type="text" class="form-control figure" value="{{ old("design.$ck", $design[$ck]) }}" maxlength="7" pattern="#[0-9a-fA-F]{6}" aria-label="{{ __($clabel) }} (hex)" data-design-hex="{{ $ck }}">
                            </div>
                            @if ($chelp)<div class="form-text">{{ __($chelp) }}</div>@endif
                            @error("design.$ck")<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
                <div class="small mt-2" id="designContrast" role="status" aria-live="polite"></div>

                <hr>
                <div class="row g-3">
                    <div class="col-6"><label class="form-label" for="dz_font">{{ __('Body font') }}</label>
                        <select id="dz_font" name="design[font]" class="form-select" data-design="font">@foreach (\App\Support\Design::FONTS as $fk => [$flabel]) <option value="{{ $fk }}" @selected($design['font'] === $fk)>{{ __($flabel) }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label" for="dz_hfont">{{ __('Heading font') }}</label>
                        <select id="dz_hfont" name="design[heading_font]" class="form-select" data-design="heading_font"><option value="same" @selected($design['heading_font'] === 'same')>{{ __('Same as body') }}</option>@foreach (\App\Support\Design::FONTS as $fk => [$flabel]) <option value="{{ $fk }}" @selected($design['heading_font'] === $fk)>{{ __($flabel) }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label" for="dz_hw">{{ __('Heading weight') }}</label>
                        <select id="dz_hw" name="design[heading_weight]" class="form-select" data-design="heading_weight">@foreach (['500' => 'Medium', '600' => 'Semi-bold', '700' => 'Bold', '800' => 'Extra bold'] as $wk => $wl)<option value="{{ $wk }}" @selected($design['heading_weight'] === $wk)>{{ __($wl) }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label" for="dz_fs">{{ __('Text size') }}: <b id="dz_fs_v">{{ $design['font_size'] }}px</b></label>
                        <input type="range" id="dz_fs" name="design[font_size]" min="14" max="19" step="1" value="{{ $design['font_size'] }}" class="form-range" data-design="font_size"></div>
                    <div class="col-6"><label class="form-label" for="dz_r">{{ __('Corner roundness') }}: <b id="dz_r_v">{{ $design['radius'] }}px</b></label>
                        <input type="range" id="dz_r" name="design[radius]" min="0" max="28" step="2" value="{{ $design['radius'] }}" class="form-range" data-design="radius"></div>
                    <div class="col-6"><label class="form-label" for="dz_btn">{{ __('Button shape') }}</label>
                        <select id="dz_btn" name="design[button]" class="form-select" data-design="button">@foreach (\App\Support\Design::BUTTONS as $bk => [$bl]) <option value="{{ $bk }}" @selected($design['button'] === $bk)>{{ __($bl) }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label" for="dz_sh">{{ __('Card shadow') }}</label>
                        <select id="dz_sh" name="design[shadow]" class="form-select" data-design="shadow">@foreach (\App\Support\Design::SHADOWS as $sk => [$sl]) <option value="{{ $sk }}" @selected($design['shadow'] === $sk)>{{ __($sl) }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label" for="dz_w">{{ __('Page width') }}</label>
                        <select id="dz_w" name="design[width]" class="form-select" data-design="width">@foreach (\App\Support\Design::WIDTHS as $wk => $wl) <option value="{{ $wk }}" @selected($design['width'] === (string) $wk)>{{ __($wl) }}</option>@endforeach</select></div>
                    <div class="col-12"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="design_apply_admin" value="1" id="dz_admin" @checked(old('design_apply_admin', $design['apply_admin']))><label class="form-check-label" for="dz_admin">{{ __('Use the same design in the admin panel') }}</label></div></div>
                </div>
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h2 class="mb-3 h5">{{ __('Logo') }}</h2>
                @if ($logo)<div class="d-flex align-items-center gap-3 mb-2"><img src="{{ brand_logo() }}" alt="" style="height:48px;border-radius:12px"><div class="form-check"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rmLogo"><label class="form-check-label" for="rmLogo">{{ __('Remove logo') }}</label></div></div>@endif
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control @error('logo') is-invalid @enderror" data-live="logo">@error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">{{ __('PNG, JPG or WebP, square works best, up to 2 MB.') }}</div>
                <label class="form-label mt-3">{{ __('Browser icon (favicon)') }}</label>
                <input type="file" name="favicon" accept="image/png,image/jpeg,image/webp,image/x-icon" class="form-control @error('favicon') is-invalid @enderror">@error('favicon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if (setting('brand.favicon'))<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_favicon" value="1" id="rmFav"><label class="form-check-label" for="rmFav">{{ __('Remove icon') }}</label></div>@endif
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h2 class="mb-3 h5">{{ __('Announcement bar') }}</h2>
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
                    <button type="button" class="btn btn-outline-secondary active" data-w="100%" aria-label="{{ __('Desktop') }}" title="{{ __('Desktop') }}"><i class="bi bi-display"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-w="390px" aria-label="{{ __('Phone') }}" title="{{ __('Phone') }}"><i class="bi bi-phone"></i></button>
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

    // ---- marketplace design ----
    var FONTS = @json(collect(\App\Support\Design::FONTS)->map(fn ($f) => $f[1])), BTN = @json(collect(\App\Support\Design::BUTTONS)->map(fn ($b) => $b[1])), SHADOW = @json(collect(\App\Support\Design::SHADOWS)->map(fn ($s) => $s[1]));
    var colorKeys = @json(array_keys(\App\Support\Design::COLORS));
    function lum(h) { var c = [1, 3, 5].map(function (i) { var v = parseInt(h.substr(i, 2), 16) / 255; return v <= .03928 ? v / 12.92 : Math.pow((v + .055) / 1.055, 2.4); }); return .2126 * c[0] + .7152 * c[1] + .0722 * c[2]; }
    function ratio(a, b) { var x = lum(a), y = lum(b); return (Math.max(x, y) + .05) / (Math.min(x, y) + .05); }
    function dval(k) { var el = form.querySelector('[data-design="' + k + '"]'); return el ? el.value : ''; }
    function applyDesign() {
        var d = frame.contentDocument; if (!d || !d.body) return;
        var s = d.body.style, set = function (n, v) { s.setProperty(n, v); };
        colorKeys.forEach(function (k) { set('--tq-' + k, dval(k)); });
        var mix = function (c, p, w) { return 'color-mix(in srgb, ' + c + ' ' + p + '%, ' + w + ')'; };
        set('--tq-navy-2', mix('var(--tq-navy)', 82, '#000')); set('--tq-blue-2', mix('var(--tq-blue)', 82, '#000')); set('--tq-orange-2', mix('var(--tq-orange)', 84, '#000'));
        set('--tq-orange-soft', mix('var(--tq-orange)', 10, '#fff')); set('--tq-sky', mix('var(--tq-blue)', 10, '#fff')); set('--tq-green-soft', mix('var(--tq-green)', 12, '#fff'));
        set('--bg', 'var(--tq-bg)'); set('--surface', 'var(--tq-card)'); set('--text', 'var(--tq-ink)'); set('--text-2', 'var(--tq-muted)'); set('--border', 'var(--tq-line)');
        set('--tq-font', FONTS[dval('font')]); set('--tq-font-head', dval('heading_font') === 'same' ? 'inherit' : FONTS[dval('heading_font')]); set('--tq-head-weight', dval('heading_weight'));
        set('--tq-r', dval('radius') + 'px'); set('--tq-r-sm', Math.max(2, Math.round(dval('radius') * .66)) + 'px'); set('--tq-btn-r', BTN[dval('button')]); set('--tq-shadow', SHADOW[dval('shadow')]);
        set('font-family', 'var(--tq-font)'); d.documentElement.style.fontSize = dval('font_size') + 'px';
        var st = d.getElementById('nx-design-live'); if (!st) { st = d.createElement('style'); st.id = 'nx-design-live'; d.head.appendChild(st); }
        st.textContent = 'body.sf-tq h1,body.sf-tq h2,body.sf-tq h3,body.sf-tq h4,body.sf-tq h5,body.sf-tq h6{font-family:var(--tq-font-head);font-weight:var(--tq-head-weight)}'
          + 'body.sf-tq .btn,body.sf-tq [class*=\"btn\"],body.sf-tq .tq-act,body.sf-tq .mk-search button{border-radius:var(--tq-btn-r)}body.sf-tq .mk-card,body.sf-tq .mk-box{box-shadow:var(--tq-shadow)}'
          + '.sf-container{max-width:' + dval('width') + 'px}';
        document.getElementById('dz_fs_v').textContent = dval('font_size') + 'px'; document.getElementById('dz_r_v').textContent = dval('radius') + 'px';
        var warn = [], c1 = ratio(dval('orange'), '#ffffff'), c2 = ratio(dval('ink'), dval('bg')), c3 = ratio(dval('blue'), dval('card')), c4 = ratio(dval('navy'), '#ffffff');
        if (c1 < 3) warn.push(@json(__('White text on the button colour is hard to read')) + ' (' + c1.toFixed(1) + ':1)');
        if (c2 < 4.5) warn.push(@json(__('Text on the page background is hard to read')) + ' (' + c2.toFixed(1) + ':1)');
        if (c3 < 4.5) warn.push(@json(__('Links and prices on cards are hard to read')) + ' (' + c3.toFixed(1) + ':1)');
        if (c4 < 4.5) warn.push(@json(__('White text on the header strip and footer is hard to read')) + ' (' + c4.toFixed(1) + ':1)');
        var box = document.getElementById('designContrast');
        box.className = 'small mt-2 ' + (warn.length ? 'text-danger' : 'text-success');
        box.innerHTML = warn.length ? '<i class="bi bi-exclamation-triangle-fill me-1"></i>' + warn.join('<br>') : '<i class="bi bi-check-circle-fill me-1"></i>' + @json(__('Contrast looks good (WCAG AA).'));
        box.querySelectorAll && 0;
    }
    function syncHex(k, v) { var h = form.querySelector('[data-design-hex="' + k + '"]'); if (h) h.value = v; }
    form.querySelectorAll('[data-design-hex]').forEach(function (h) {
        h.addEventListener('input', function () { if (/^#[0-9a-fA-F]{6}$/.test(h.value)) { form.querySelector('[data-design="' + h.dataset.designHex + '"]').value = h.value; applyDesign(); } });
    });
    form.querySelectorAll('input[type=color][data-design]').forEach(function (c) { c.addEventListener('input', function () { syncHex(c.dataset.design, c.value); }); });
    document.querySelectorAll('[data-preset]').forEach(function (b) {
        b.addEventListener('click', function () {
            var p = JSON.parse(b.dataset.preset);
            Object.keys(p).forEach(function (k) { var el = form.querySelector('[data-design="' + k + '"]'); if (el) { el.value = p[k]; syncHex(k, p[k]); } });
            applyDesign();
        });
    });
    document.getElementById('designResetBtn').addEventListener('click', function () {
        form.querySelectorAll('[data-design]').forEach(function (el) { if (el.dataset['default']) { el.value = el.dataset['default']; syncHex(el.dataset.design, el.value); } });
        var dflt = { font: 'inter', heading_font: 'same', heading_weight: '700', font_size: '16', radius: '12', button: 'rounded', shadow: 'soft', width: '1280' };
        Object.keys(dflt).forEach(function (k) { var el = form.querySelector('[data-design="' + k + '"]'); if (el) el.value = dflt[k]; });
        document.getElementById('dz_admin').checked = true; document.getElementById('designReset').value = '1';
        applyDesign();
    });
    form.addEventListener('input', function (e) { if (e.target.matches('[data-design],[data-design-hex]')) { document.getElementById('designReset').value = '0'; applyDesign(); } });
    form.addEventListener('change', function (e) { if (e.target.matches('[data-design]')) applyDesign(); });
    frame.addEventListener('load', applyDesign);
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
