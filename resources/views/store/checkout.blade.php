@extends('layouts.store')
@php $page = 'checkout'; @endphp
@section('title', __('Checkout'))

@section('content')
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><a href="{{ route('store.cart') }}">{{ __('Your cart') }}</a><span>›</span><span>{{ __('Checkout') }}</span></nav>
    <div class="mk-page-head">
        <h1>{{ __('Checkout') }}</h1>
        <span class="mk-secure"><i class="bi bi-lock-fill"></i> {{ __('Secure checkout') }}</span>
    </div>
    <ol class="mk-progress" aria-label="{{ __('Checkout steps') }}">
        <li class="done"><i class="bi bi-check-lg"></i><span>{{ __('Your cart') }}</span></li>
        <li class="now"><b>2</b><span>{{ __('Details & payment') }}</span></li>
        <li><b>3</b><span>{{ __('Done') }}</span></li>
    </ol>

    <form method="POST" action="{{ route('store.checkout.place') }}" enctype="multipart/form-data" id="ckForm" novalidate>
        @csrf
        <div class="row g-4 g-xl-5">
            {{-- ============ left: the form ============ --}}
            <div class="col-lg-7 order-2 order-lg-1">
                @if ($errors->any())
                    <div class="sf-alert mb-3" role="alert"><i class="bi bi-exclamation-triangle-fill fs-5"></i><div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
                @endif

                <section class="ck-sec">
                    <div class="d-flex justify-content-between align-items-center"><h2><span class="mk-step">1</span>{{ __('Contact') }}</h2>
                        @guest<a href="{{ route('login') }}" class="small fw-bold">{{ __('Have an account? Sign in') }}</a>@endguest</div>
                    @guest<p class="fx-guest-note"><i class="bi bi-person-check"></i> {{ __('No account needed — you are checking out as a guest. You can create an account after ordering.') }}</p>@endguest
                    <div class="row g-3">
                        <div class="col-md-7"><label class="form-label" for="ckEmail">{{ __('Email') }}</label><input id="ckEmail" type="email" name="email" value="{{ old('email', $defaults['email']) }}" required autocomplete="email" class="form-control @error('email') is-invalid @enderror"></div>
                        <div class="col-md-5"><label class="form-label" for="ckPhone">{{ __('Phone') }}</label><input id="ckPhone" type="tel" name="phone" value="{{ old('phone', $defaults['phone']) }}" required autocomplete="tel" inputmode="tel" class="form-control @error('phone') is-invalid @enderror"></div>
                    </div>
                </section>

                <section class="ck-sec">
                    <h2><span class="mk-step">2</span>{{ __('Delivery') }}</h2>
                    <div class="row g-3" id="addrBox">
                        <div class="col-12"><label class="form-label" for="ckName">{{ __('Full name') }}</label><input id="ckName" name="name" value="{{ old('name', $defaults['name']) }}" required autocomplete="name" class="form-control @error('name') is-invalid @enderror"></div>
                        <div class="col-12 addr-only"><label class="form-label" for="ckAddr">{{ __('Address') }}</label><input id="ckAddr" name="address_line" value="{{ old('address_line', $defaults['address_line']) }}" autocomplete="street-address" class="form-control @error('address_line') is-invalid @enderror" placeholder="{{ __('House, street, landmark') }}"></div>
                        <div class="col-md-5 addr-only"><label class="form-label" for="ckCity">{{ __('City') }}</label><input id="ckCity" name="city" value="{{ old('city', $defaults['city']) }}" list="ckCities" autocomplete="address-level2" class="form-control @error('city') is-invalid @enderror">
                            <datalist id="ckCities">@foreach ($cities as $c)<option value="{{ $c }}">@endforeach</datalist></div>
                        @if (sf('checkout.show_area'))<div class="col-md-4 addr-only"><label class="form-label" for="ckArea">{{ __('Area') }}</label><input id="ckArea" name="area" value="{{ old('area', $defaults['area']) }}" class="form-control"></div>@endif
                        @if (sf('checkout.show_postal'))<div class="col-md-3 addr-only"><label class="form-label" for="ckPostal">{{ __('Postal code') }}</label><input id="ckPostal" name="postal_code" value="{{ old('postal_code', $defaults['postal_code']) }}" autocomplete="postal-code" class="form-control figure"></div>@endif
                    </div>
                </section>

                <section class="ck-sec">
                    <h2><span class="mk-step">3</span>{{ __('Shipping method') }}</h2>
                    <div id="rates" class="d-grid gap-2" role="radiogroup">
                        @forelse ($q['rates'] as $r)
                            <label class="ck-opt"><input type="radio" name="shipping_rate_id" value="{{ $r->id }}" data-pickup="{{ $r->is_pickup ? 1 : 0 }}" @checked($q['rate']?->id === $r->id)>
                                <span><b>{{ $r->name }}</b>@if ($r->eta)<small>{{ $r->eta }}</small>@endif</span>
                                <em class="figure">{{ $r->costFor($q['subtotal']) > 0 ? money($r->costFor($q['subtotal'])) : __('Free') }}</em></label>
                        @empty
                            <div class="text-muted small" id="noRates">{{ __('Enter your city to see delivery options.') }}</div>
                        @endforelse
                    </div>
                    @error('shipping_rate_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                </section>

                @if ($slotDays)
                    <section class="ck-sec" id="slotSec">
                        <h2><span class="mk-step"><i class="bi bi-clock"></i></span>{{ __('Delivery time') }}</h2>
                        <div class="sf-pills mb-3" id="slotDays" role="radiogroup" aria-label="{{ __('Delivery day') }}">
                            @foreach ($slotDays as $i => $d)
                                <label class="sf-pill"><input type="radio" name="slot_date" value="{{ $d['date'] }}" @checked(old('slot_date', collect($slotDays)->first(fn ($x) => collect($x['slots'])->contains('open', true))['date'] ?? $slotDays[0]['date']) === $d['date'])><span>{{ $d['label'] }}</span></label>
                            @endforeach
                        </div>
                        @foreach ($slotDays as $d)
                            <div class="d-grid gap-2 slot-day" data-date="{{ $d['date'] }}" hidden>
                                @foreach ($d['slots'] as $sl)
                                    <label class="ck-opt" style="{{ $sl['open'] ? '' : 'opacity:.45;cursor:not-allowed' }}"><input type="radio" name="delivery_slot_id" value="{{ $sl['id'] }}" @disabled(! $sl['open']) @checked((int) old('delivery_slot_id') === $sl['id'] && old('slot_date') === $d['date'])>
                                        <span><b>{{ $sl['label'] }}</b>@if (! $sl['open'])<small>{{ $sl['left'] === 0 ? __('Full') : __('Closed') }}</small>@elseif ($sl['left'] !== null && $sl['left'] <= 3)<small class="text-warning">{{ __(':n places left', ['n' => $sl['left']]) }}</small>@endif</span><i class="bi bi-clock"></i></label>
                                @endforeach
                            </div>
                        @endforeach
                        @error('delivery_slot_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </section>
                @endif

                @if ($needsRx)
                    <section class="ck-sec">
                        <h2><i class="bi bi-prescription2 me-1"></i>{{ __('Prescription') }}</h2>
                        <p class="text-muted small">{{ __('Your cart has items that need a prescription. Upload a clear photo or PDF — our pharmacist will verify it before dispatch.') }}</p>
                        <input type="file" name="prescription[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf" class="form-control @error('prescription') is-invalid @enderror @error('prescription.*') is-invalid @enderror" required>
                        @error('prescription')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @error('prescription.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </section>
                @endif

                <section class="ck-sec">
                    <h2><span class="mk-step"><i class="bi bi-credit-card"></i></span>{{ __('Payment') }}</h2>
                    @if (empty($gateways))
                        <div class="sf-alert">{{ __('No payment method is available right now. Please contact the store.') }}</div>
                    @endif
                    <div class="d-grid gap-2" role="radiogroup">
                        @foreach ($gateways as $key => $g)
                            <label class="ck-opt"><input type="radio" name="payment_method" value="{{ $key }}" @checked(old('payment_method', array_key_first($gateways)) === $key) required>
                                <span><b>{{ $g->label() }}</b><small>{{ $g->description() }}</small></span>
                                <i class="bi bi-{{ ['cod' => 'cash-coin', 'bank' => 'bank', 'stripe' => 'credit-card-2-front'][$key] ?? 'wallet2' }} fs-5"></i></label>
                        @endforeach
                    </div>
                    @error('payment_method')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                </section>

                @if (sf('checkout.show_notes'))<section class="ck-sec">
                    <label class="form-label" for="ckNotes">{{ __('Order notes') }} <span class="text-muted">({{ __('optional') }})</span></label>
                    <textarea id="ckNotes" name="notes" rows="2" maxlength="500" class="form-control" placeholder="{{ __('Anything we should know?') }}">{{ old('notes') }}</textarea>
                </section>@endif

                <input type="hidden" name="coupon" id="couponField" value="{{ old('coupon', session('coupon')) }}">
                <x-honeypot />
                <x-captcha />
                <button type="submit" class="btn btn-primary btn-lg w-100 py-3" id="placeBtn" @disabled(empty($gateways))><i class="bi bi-lock-fill me-1"></i> {{ __('Place order') }} · <span data-total>{{ money($q['total']) }}</span></button>
                <p class="text-center text-muted small mt-2">{{ __('By placing your order you agree to be contacted about its delivery.') }}</p>
            </div>

            {{-- ============ right: summary ============ --}}
            <div class="col-lg-5 order-1 order-lg-2">
                <aside class="mk-summary ck-summary">
                    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="mb-0">{{ __('Order summary') }}</h2><a href="{{ route('store.cart') }}" class="small fw-bold">{{ __('Edit cart') }}</a></div>
                    @foreach ($lines['lines'] as $l)
                        <div class="ck-line">
                            <div class="ck-thumb">@if ($l['image'])<img src="{{ $l['image'] }}" alt="">@else<i class="bi bi-box-seam"></i>@endif<span>{{ $l['qty'] }}</span></div>
                            <div class="min-w-0"><div class="fw-semibold text-truncate">{{ $l['name'] }}</div>@php($o = $l['variant'])@if ($o)<small class="text-muted">{{ $o }}</small>@endif</div>
                            <div class="figure">{{ $l['total'] }}</div>
                        </div>
                    @endforeach

                    <div class="d-flex gap-2 my-3">
                        <input type="text" id="couponInput" class="form-control" placeholder="{{ __('Discount code') }}" value="{{ session('coupon') }}" autocomplete="off" aria-label="{{ __('Discount code') }}">
                        <button type="button" class="btn btn-outline-secondary" id="couponBtn">{{ __('Apply') }}</button>
                    </div>
                    <div class="small mb-2" id="couponMsg">@if ($q['coupon'] && ! $q['coupon_problem'])<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>{{ $q['coupon']->code }} · {{ $q['coupon']->label() }}</span>@endif</div>

                    <div class="ck-row"><span>{{ __('Subtotal') }}</span><b class="figure" data-sub>{{ money($q['subtotal']) }}</b></div>
                    <div class="ck-row" id="rowDiscount" @if ($q['discount'] <= 0) hidden @endif><span>{{ __('Discount') }}</span><b class="figure text-success" data-discount>−{{ money($q['discount']) }}</b></div>
                    <div class="ck-row"><span>{{ __('Shipping') }}</span><b class="figure" data-ship>{{ $q['rate'] ? ($q['shipping'] > 0 ? money($q['shipping']) : __('Free')) : '—' }}</b></div>
                    <div class="ck-row" id="rowTax" @if ($q['tax'] <= 0) hidden @endif><span>{{ __('Tax') }} <small class="text-muted" data-taxnote>@if ($q['tax_inclusive'])({{ __('included') }})@endif</small></span><b class="figure" data-tax>{{ money($q['tax']) }}</b></div>
                    <div class="ck-row ck-total"><span>{{ __('Total') }}</span><b class="figure" data-total>{{ money($q['total']) }}</b></div>
                    <ul class="mk-sum-trust">
                        <li><i class="bi bi-shield-check"></i> {{ __('Secure checkout') }}</li>
                        <li><i class="bi bi-truck"></i> {{ __('Delivery to your city') }}</li>
                        <li><i class="bi bi-arrow-counterclockwise"></i> {{ __('Easy returns') }}</li>
                    </ul>
                </aside>
            </div>
        </div>
    </form>
</div>
@endsection

@push('head')
<style>
    .slot-day[hidden] { display: none !important; }   /* d-grid would otherwise win over the hidden attribute */
    .ck-sec { padding-bottom: 1.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); }
    .ck-sec h2 { font-size: 1.1rem; margin-bottom: 1rem; }
    .ck-opt { display: grid; grid-template-columns: auto 1fr auto; gap: .8rem; align-items: center; padding: .9rem 1rem; border: 1px solid var(--border-strong); border-radius: 14px; background: var(--surface); cursor: pointer; transition: .2s var(--ease); }
    .ck-opt:hover { border-color: var(--acc-1); }
    .ck-opt:has(input:checked) { border-color: var(--acc-1); background: color-mix(in srgb, var(--acc-1) 10%, var(--surface)); box-shadow: var(--glow); }
    .ck-opt small { display: block; color: var(--text-2); font-size: .82rem; }
    .ck-summary { position: sticky; top: 90px; }
    .ck-line { display: grid; grid-template-columns: 56px 1fr auto; gap: .8rem; align-items: center; padding: .55rem 0; }
    .ck-thumb { position: relative; width: 56px; height: 56px; border-radius: 12px; background: var(--surface-2); display: grid; place-items: center; }
    .ck-thumb img { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; }
    .ck-thumb span { position: absolute; top: -7px; inset-inline-end: -7px; min-width: 20px; height: 20px; border-radius: 99px; background: var(--grad); color: #fff; font-size: .7rem; font-weight: 700; display: grid; place-items: center; font-family: var(--font-mono); }
    .ck-row { display: flex; justify-content: space-between; padding: .3rem 0; color: var(--text-2); }
    .ck-row b { color: var(--text); font-weight: 600; }
    .ck-total { border-top: 1px solid var(--border); margin-top: .5rem; padding-top: .8rem; font-size: 1.15rem; color: var(--text); }
    .ck-total b { font-size: 1.35rem; }
    .ck-opt input { accent-color: var(--acc-1); }
</style>
@endpush

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    var form = document.getElementById('ckForm'), csrf = document.querySelector('meta[name=csrf-token]').content;
    var $ = function (s) { return form.querySelector(s); }, T = {{ \Illuminate\Support\Js::from(['enterCity' => __('Enter your city to see delivery options.'), 'free' => __('Free'), 'applied' => __('Applied')]) }};
    var city = $('#ckCity'), couponInput = document.getElementById('couponInput'), couponField = document.getElementById('couponField'), timer;

    function selectedRate() { var r = $('input[name=shipping_rate_id]:checked'); return r ? +r.value : null; }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    function toggleAddress() {
        var r = $('input[name=shipping_rate_id]:checked'), pickup = r && r.dataset.pickup === '1';
        form.querySelectorAll('.addr-only').forEach(function (el) { el.style.display = pickup ? 'none' : ''; });
    }

    function paint(d) {
        document.querySelectorAll('[data-sub]').forEach(function (e) { e.textContent = d.subtotal; });
        document.querySelectorAll('[data-total]').forEach(function (e) { e.textContent = d.total; });
        $('#rowDiscount') && (document.getElementById('rowDiscount').hidden = !d.discount);
        document.querySelector('[data-discount]').textContent = d.discount || '';
        document.querySelector('[data-ship]').textContent = d.shipping || '—';
        document.getElementById('rowTax').hidden = !d.tax;
        document.querySelector('[data-tax]').textContent = d.tax || '';
        document.querySelector('[data-taxnote]').textContent = d.tax_inclusive ? '(' + {{ \Illuminate\Support\Js::from(__('included')) }} + ')' : '';
        var msg = document.getElementById('couponMsg');
        if (d.coupon) { msg.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>' + esc(d.coupon.code) + ' · ' + esc(d.coupon.label) + '</span>'; couponField.value = d.coupon.code; }
        else { msg.innerHTML = d.coupon_error ? '<span class="text-danger">' + esc(d.coupon_error) + '</span>' : ''; couponField.value = ''; }

        var box = document.getElementById('rates'), keep = d.rate_id || selectedRate();
        if (!d.rates.length) { box.innerHTML = '<div class="text-muted small">' + esc(T.enterCity) + '</div>'; toggleAddress(); return; }
        box.innerHTML = d.rates.map(function (r) {
            var chosen = String(keep) === String(r.id);
            return '<label class="ck-opt"><input type="radio" name="shipping_rate_id" value="' + r.id + '" data-pickup="' + (r.pickup ? 1 : 0) + '"' + (chosen ? ' checked' : '') + '>' +
                '<span><b>' + esc(r.name) + '</b>' + (r.eta ? '<small>' + esc(r.eta) + '</small>' : '') + '</span><em class="figure">' + esc(r.price) + '</em></label>';
        }).join('');
        if (!$('input[name=shipping_rate_id]:checked')) { var f = $('input[name=shipping_rate_id]'); if (f) { f.checked = true; requote(); } }
        toggleAddress();
    }

    function requote() {
        fetch({{ \Illuminate\Support\Js::from(route('store.checkout.quote')) }}, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ city: city.value, rate_id: selectedRate(), coupon: couponField.value || couponInput.value || null })
        }).then(function (r) { return r.json(); }).then(paint).catch(function () {});
    }

    city.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(requote, 350); });
    city.addEventListener('change', requote);
    form.addEventListener('change', function (e) { if (e.target.name === 'shipping_rate_id') { toggleAddress(); requote(); } });
    // remember the cart once a valid e-mail is typed, so a forgotten cart can be recovered
    var email = document.getElementById('ckEmail');
    email.addEventListener('change', function () {
        if (!email.validity.valid || !email.value) return;
        fetch({{ \Illuminate\Support\Js::from(route('store.checkout.capture')) }}, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ email: email.value }) }).catch(function () {});
    });
    document.getElementById('couponBtn').addEventListener('click', function () { couponField.value = couponInput.value.trim(); requote(); });
    couponInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); couponField.value = couponInput.value.trim(); requote(); } });
    form.addEventListener('submit', function () { var b = document.getElementById('placeBtn'); setTimeout(function () { b.disabled = true; }, 0); });
    // delivery time: show the slots of the chosen day
    var dayRadios = form.querySelectorAll('input[name=slot_date]');
    function showDay() {
        var d = (form.querySelector('input[name=slot_date]:checked') || {}).value;
        form.querySelectorAll('.slot-day').forEach(function (el) {
            var on = el.dataset.date === d; el.hidden = !on;
            if (!on) el.querySelectorAll('input').forEach(function (i) { i.checked = false; });
        });
    }
    dayRadios.forEach(function (r) { r.addEventListener('change', showDay); });
    if (dayRadios.length) showDay();
    toggleAddress();
})();
</script>
@endpush
