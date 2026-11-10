@extends('layouts.checkout')
@php $page = 'checkout'; @endphp
@section('title', __('Checkout'))

@section('content')
@php
    $storeName = setting('store.name', config('app.name'));
    $footerMenu = \App\Models\MenuItem::resolved('footer');
    $customLogo = setting('brand.logo') ? brand_logo() : null;
    $nameParts = preg_split('/\s+/', trim((string) old('name', $defaults['name'])), 2);
    $hasPickup = $q['rates']->contains('is_pickup', true);
    $mode = $q['rate']?->is_pickup ? 'pickup' : 'ship';
    $itemCount = collect($lines['lines'])->sum('qty');
    $cur = strtoupper((string) setting('store.currency', 'PKR'));
    $country = setting('store.country', 'Pakistan');
@endphp
<form method="POST" action="{{ route('store.checkout.place') }}" enctype="multipart/form-data" id="ckForm" class="shck" novalidate>
    @csrf
    <header class="shck-head">
        <a href="{{ route('store.home') }}" class="shck-brand" aria-label="{{ $storeName }}">@if ($customLogo)<img src="{{ $customLogo }}" alt="{{ $storeName }}">@else<span>{{ $storeName }}</span>@endif</a>
        <a href="{{ route('store.cart') }}" class="shck-bag" aria-label="{{ __('Your cart') }}"><i class="bi bi-bag"></i></a>
    </header>

    {{-- ============ order summary (right on desktop, a collapsible bar on phones) ============ --}}
    <div class="shck-side">
        <aside class="sp" aria-label="{{ __('Order summary') }}">
            <button type="button" class="sp-toggle" data-bs-toggle="collapse" data-bs-target="#spBody" aria-expanded="false" aria-controls="spBody">
                <span class="sp-tl"><span>{{ __('Order summary') }}</span><i class="bi bi-chevron-down sp-chev"></i></span>
                <b data-total>{{ money($q['total']) }}</b>
            </button>
            <div id="spBody" class="sp-body collapse">
                <div class="sp-lines">
                    @foreach ($lines['lines'] as $l)
                        <div class="sp-line">
                            <div class="sp-thumb">@if ($l['image'])<img src="{{ $l['image'] }}" alt="">@else<i class="bi bi-box-seam"></i>@endif<span class="sp-qty" aria-label="{{ __('Quantity') }}">{{ $l['qty'] }}</span></div>
                            <div class="sp-name"><b>{{ $l['name'] }}</b>@if ($l['variant'])<small>{{ $l['variant'] }}</small>@endif</div>
                            <div class="sp-price">{{ $l['total'] }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="sp-code">
                    <div class="sp-field"><input type="text" id="couponInput" maxlength="40" placeholder=" " value="{{ session('coupon') }}" autocomplete="off"><label for="couponInput">{{ __('Discount code') }}</label></div>
                    <button type="button" id="couponBtn">{{ __('Apply') }}</button>
                </div>
                <div class="sp-codemsg" id="couponMsg" role="status">@if ($q['coupon'] && ! $q['coupon_problem'])<span class="sp-tag"><i class="bi bi-tag"></i> {{ $q['coupon']->code }}</span>@endif</div>

                <dl class="sp-rows">
                    <div class="sp-row"><dt>{{ __('Subtotal') }} <span class="sp-dim">· {{ trans_choice(':n item|:n items', $itemCount, ['n' => $itemCount]) }}</span></dt><dd data-sub>{{ money($q['subtotal']) }}</dd></div>
                    <div class="sp-row" id="rowDiscount" @if ($q['discount'] <= 0) hidden @endif><dt>{{ __('Discount') }}</dt><dd data-discount>−{{ money($q['discount']) }}</dd></div>
                    <div class="sp-row"><dt>{{ __('Shipping') }}</dt><dd data-ship class="@unless ($q['rate']) sp-dim @endunless">{{ $q['rate'] ? ($q['shipping'] > 0 ? money($q['shipping']) : __('Free')) : __('Enter shipping address') }}</dd></div>
                    <div class="sp-row" id="rowTax" @if ($q['tax'] <= 0 || $q['tax_inclusive']) hidden @endif><dt>{{ __('Estimated taxes') }}</dt><dd data-tax>{{ money($q['tax']) }}</dd></div>
                </dl>
                <div class="sp-total"><span>{{ __('Total') }}</span><span class="sp-totalamt"><small>{{ $cur }}</small><b data-total>{{ money($q['total']) }}</b></span></div>
                <p class="sp-incl" id="rowInclTax" @if ($q['tax'] <= 0 || ! $q['tax_inclusive']) hidden @endif>{{ __('Including') }} <span data-tax-incl>{{ money($q['tax']) }}</span> {{ __('in taxes') }}</p>
                <p class="sp-save" id="rowSave" @if ($q['discount'] <= 0) hidden @endif><i class="bi bi-tag"></i> {{ __('Total savings') }} <b data-save>{{ money($q['discount']) }}</b></p>
            </div>
        </aside>
    </div>

    {{-- ============ the form ============ --}}
    <main class="shck-main" id="ckMain">
        @if ($errors->any())
            <div class="shck-alert" role="alert"><i class="bi bi-exclamation-circle-fill"></i><div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
        @endif

        <section class="shck-sec" id="ckContact">
            <div class="shck-h"><h2>{{ __('Contact') }}</h2>@guest<a href="{{ route('login') }}">{{ __('Sign in') }}</a>@endguest</div>
            <div class="shf"><input id="ckEmail" type="email" name="email" value="{{ old('email', $defaults['email']) }}" placeholder=" " required maxlength="190" autocomplete="email" class="@error('email') is-invalid @enderror"><label for="ckEmail">{{ __('Email') }}</label>@error('email')<div class="shf-err">{{ $message }}</div>@enderror</div>
            <label class="shck-check"><input type="checkbox" name="news" value="1" @checked(old('news'))><span class="shck-box"></span><span>{{ __('Email me with news and offers') }}</span></label>
        </section>

        <section class="shck-sec" id="ckDelivery">
            <div class="shck-h"><h2>{{ __('Delivery') }}</h2></div>
            @if ($hasPickup)
                <div class="shck-group shck-mode" role="radiogroup" aria-label="{{ __('Delivery method') }}">
                    <label class="shck-opt"><input type="radio" name="delivery_mode" value="ship" @checked($mode === 'ship')><span class="shck-radio"></span><span class="shck-ot">{{ __('Ship') }}</span><i class="bi bi-truck"></i></label>
                    <label class="shck-opt"><input type="radio" name="delivery_mode" value="pickup" @checked($mode === 'pickup')><span class="shck-radio"></span><span class="shck-ot">{{ __('Pick up') }}</span><i class="bi bi-shop"></i></label>
                </div>
            @endif
            <div class="shck-grid" id="addrBox">
                <div class="shf g12 addr-only"><select id="ckCountry" name="country" aria-label="{{ __('Country/Region') }}"><option>{{ $country }}</option></select><label for="ckCountry">{{ __('Country/Region') }}</label><i class="bi bi-chevron-down shf-caret"></i></div>
                <div class="shf g6"><input id="ckFirst" name="first_name" value="{{ old('first_name', $nameParts[0] ?? '') }}" placeholder=" " required maxlength="60" autocomplete="given-name" class="@error('first_name') is-invalid @enderror @error('name') is-invalid @enderror"><label for="ckFirst">{{ __('First name') }}</label></div>
                <div class="shf g6"><input id="ckLast" name="last_name" value="{{ old('last_name', $nameParts[1] ?? '') }}" placeholder=" " maxlength="60" autocomplete="family-name" class="@error('last_name') is-invalid @enderror"><label for="ckLast">{{ __('Last name') }}</label></div>
                <div class="shf g12 addr-only"><input id="ckAddr" name="address_line" value="{{ old('address_line', $defaults['address_line']) }}" placeholder=" " maxlength="255" autocomplete="street-address" class="@error('address_line') is-invalid @enderror"><label for="ckAddr">{{ __('Address') }}</label>@error('address_line')<div class="shf-err">{{ $message }}</div>@enderror</div>
                @if (sf('checkout.show_area'))<div class="shf g12 addr-only"><input id="ckArea" name="area" value="{{ old('area', $defaults['area']) }}" placeholder=" " maxlength="120" autocomplete="address-line2"><label for="ckArea">{{ __('Apartment, suite, etc. (optional)') }}</label></div>@endif
                <div class="shf g6 addr-only"><input id="ckCity" name="city" value="{{ old('city', $defaults['city']) }}" placeholder=" " maxlength="120" list="ckCities" autocomplete="address-level2" class="@error('city') is-invalid @enderror"><label for="ckCity">{{ __('City') }}</label><datalist id="ckCities">@foreach ($cities as $c)<option value="{{ $c }}">@endforeach</datalist>@error('city')<div class="shf-err">{{ $message }}</div>@enderror</div>
                @if (sf('checkout.show_postal'))<div class="shf g6 addr-only"><input id="ckPostal" name="postal_code" value="{{ old('postal_code', $defaults['postal_code']) }}" placeholder=" " maxlength="20" autocomplete="postal-code"><label for="ckPostal">{{ __('Postal code') }} ({{ __('optional') }})</label></div>@endif
                <div class="shf g12"><input id="ckPhone" type="tel" name="phone" value="{{ old('phone', $defaults['phone']) }}" placeholder=" " required autocomplete="tel" inputmode="tel" maxlength="40" pattern="[0-9+\-()\s]{6,}" title="{{ __('Digits, spaces, + - ( ) only') }}" class="@error('phone') is-invalid @enderror"><label for="ckPhone">{{ __('Phone') }}</label>@error('phone')<div class="shf-err">{{ $message }}</div>@enderror</div>
            </div>
            @error('name')<div class="shf-err">{{ $message }}</div>@enderror
        </section>

        <section class="shck-sec" id="ckShip">
            <div class="shck-h"><h2>{{ __('Shipping method') }}</h2></div>
            <div id="rates" class="shck-group" role="radiogroup" aria-label="{{ __('Shipping method') }}">
                @forelse ($q['rates'] as $r)
                    <label class="ck-opt shck-opt"><input type="radio" name="shipping_rate_id" value="{{ $r->id }}" data-pickup="{{ $r->is_pickup ? 1 : 0 }}" @checked($q['rate']?->id === $r->id)><span class="shck-radio"></span>
                        <span class="shck-ot"><b>{{ $r->name }}</b>@if ($r->eta)<small>{{ $r->eta }}</small>@endif</span>
                        <em class="figure">{{ $r->costFor($q['subtotal']) > 0 ? money($r->costFor($q['subtotal'])) : __('Free') }}</em></label>
                @empty
                    <div class="shck-empty" id="noRates">{{ __('Enter your shipping address to view available shipping methods.') }}</div>
                @endforelse
            </div>
            @error('shipping_rate_id')<div class="shf-err">{{ $message }}</div>@enderror
        </section>

        @if ($slotDays)
            <section class="shck-sec" id="slotSec">
                <div class="shck-h"><h2>{{ __('Delivery time') }}</h2></div>
                <div class="shck-pills" id="slotDays" role="radiogroup" aria-label="{{ __('Delivery day') }}">
                    @foreach ($slotDays as $i => $d)
                        <label class="shck-pill"><input type="radio" name="slot_date" value="{{ $d['date'] }}" @checked(old('slot_date', collect($slotDays)->first(fn ($x) => collect($x['slots'])->contains('open', true))['date'] ?? $slotDays[0]['date']) === $d['date'])><span>{{ $d['label'] }}</span></label>
                    @endforeach
                </div>
                @foreach ($slotDays as $d)
                    <div class="shck-group slot-day" data-date="{{ $d['date'] }}" hidden>
                        @foreach ($d['slots'] as $sl)
                            <label class="ck-opt shck-opt {{ $sl['open'] ? '' : 'is-off' }}"><input type="radio" name="delivery_slot_id" value="{{ $sl['id'] }}" @disabled(! $sl['open']) @checked((int) old('delivery_slot_id') === $sl['id'])><span class="shck-radio"></span>
                                <span class="shck-ot"><b>{{ $sl['label'] }}</b>@if (! $sl['open'])<small>{{ $sl['left'] === 0 ? __('Full') : __('Closed') }}</small>@elseif ($sl['left'] !== null && $sl['left'] <= 3)<small class="shck-warn">{{ __(':n places left', ['n' => $sl['left']]) }}</small>@endif</span><i class="bi bi-clock"></i></label>
                        @endforeach
                    </div>
                @endforeach
                @error('delivery_slot_id')<div class="shf-err">{{ $message }}</div>@enderror
            </section>
        @endif

        @if ($needsRx)
            <section class="shck-sec">
                <div class="shck-h"><h2>{{ __('Prescription') }}</h2></div>
                <p class="shck-sub">{{ __('Your cart has items that need a prescription. Upload a clear photo or PDF — our pharmacist will verify it before dispatch.') }}</p>
                <input type="file" name="prescription[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf" required class="shck-file @error('prescription') is-invalid @enderror @error('prescription.*') is-invalid @enderror">
                @error('prescription')<div class="shf-err">{{ $message }}</div>@enderror
                @error('prescription.*')<div class="shf-err">{{ $message }}</div>@enderror
            </section>
        @endif

        <section class="shck-sec" id="ckPay">
            <div class="shck-h"><h2>{{ __('Payment') }}</h2></div>
            <p class="shck-sub">{{ __('All transactions are secure and encrypted.') }}</p>
            @if (empty($gateways))
                <div class="shck-alert"><i class="bi bi-exclamation-circle-fill"></i><div>{{ __('No payment method is available right now. Please contact the store.') }}</div></div>
            @endif
            <div class="shck-group" role="radiogroup" aria-label="{{ __('Payment') }}">
                @foreach ($gateways as $key => $g)
                    <div class="shck-pm">
                        <label class="shck-opt"><input type="radio" name="payment_method" value="{{ $key }}" data-gw="{{ $key }}" @checked(old('payment_method', array_key_first($gateways)) === $key) required><span class="shck-radio"></span><span class="shck-ot"><b>{{ $g->label() }}</b></span>
                            <i class="bi bi-{{ ['cod' => 'cash-coin', 'bank' => 'bank', 'stripe' => 'credit-card-2-front'][$key] ?? 'wallet2' }}"></i></label>
                        @if ($g->description())<div class="shck-pm-d">{{ $g->description() }}</div>@endif
                    </div>
                @endforeach
            </div>
            @error('payment_method')<div class="shf-err">{{ $message }}</div>@enderror
        </section>

        @if (sf('checkout.show_notes'))<section class="shck-sec">
            <div class="shf"><textarea id="ckNotes" name="notes" rows="2" maxlength="500" placeholder=" ">{{ old('notes') }}</textarea><label for="ckNotes">{{ __('Order notes') }} ({{ __('optional') }})</label></div>
        </section>@endif

        <input type="hidden" name="coupon" id="couponField" value="{{ old('coupon', session('coupon')) }}">
        <x-honeypot />
        <x-captcha />
        <button type="submit" class="shck-pay" id="placeBtn" @disabled(empty($gateways))><span class="shck-pay-t" id="payLabel">{{ __('Complete order') }}</span></button>
        <p class="shck-fine">{{ __('By placing your order you agree to be contacted about its delivery.') }}</p>

        <footer class="shck-foot">
            <nav aria-label="{{ __('Legal') }}">@foreach ($footerMenu->take(6) as $m)<a href="{{ $m['href'] }}">{{ $m['label'] }}</a>@endforeach</nav>
        </footer>
    </main>
</form>
@endsection

@push('head')
<style>.slot-day[hidden] { display: none !important; }</style>
@endpush

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    var form = document.getElementById('ckForm'), csrf = document.querySelector('meta[name=csrf-token]').content;
    var $ = function (s) { return form.querySelector(s); }, T = {{ \Illuminate\Support\Js::from(['enterCity' => __('Enter your shipping address to view available shipping methods.'), 'free' => __('Free'), 'enterShip' => __('Enter shipping address'), 'pay' => __('Pay now'), 'complete' => __('Complete order')]) }};
    var city = $('#ckCity'), couponInput = document.getElementById('couponInput'), couponField = document.getElementById('couponField'), timer;

    function selectedRate() { var r = $('input[name=shipping_rate_id]:checked'); return r ? +r.value : null; }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    // Ship / Pick up switch: shows only the matching delivery methods
    function mode() { var m = $('input[name=delivery_mode]:checked'); return m ? m.value : null; }
    function applyMode() {
        var m = mode(); if (!m) return;
        var rows = form.querySelectorAll('#rates .ck-opt'), anyShown = false;
        rows.forEach(function (row) { var p = row.querySelector('input').dataset.pickup === '1', show = (m === 'pickup') === p; row.hidden = !show; if (show) anyShown = true; });
        var cur = $('input[name=shipping_rate_id]:checked');
        if (!cur || cur.closest('.ck-opt').hidden) { var first = form.querySelector('#rates .ck-opt:not([hidden]) input'); if (first) { first.checked = true; requote(); } }
    }
    function toggleAddress() {
        var r = $('input[name=shipping_rate_id]:checked'), pickup = r && r.dataset.pickup === '1';
        form.querySelectorAll('.addr-only').forEach(function (el) { el.style.display = pickup ? 'none' : ''; });
    }
    function payLabel() { var g = $('input[name=payment_method]:checked'); document.getElementById('payLabel').textContent = g && g.dataset.gw === 'stripe' ? T.pay : T.complete; }

    function paint(d) {
        document.querySelectorAll('[data-sub]').forEach(function (e) { e.textContent = d.subtotal; });
        document.querySelectorAll('[data-total]').forEach(function (e) { e.textContent = d.total; });
        var rd = document.getElementById('rowDiscount'); rd.hidden = !d.discount;
        document.querySelector('[data-discount]').textContent = d.discount || '';
        var sv = document.getElementById('rowSave'); sv.hidden = !d.discount; document.querySelector('[data-save]').textContent = d.discount ? d.discount.replace('−', '') : '';
        var sh = document.querySelector('[data-ship]'); sh.textContent = d.shipping || T.enterShip; sh.classList.toggle('sp-dim', !d.shipping);
        document.getElementById('rowTax').hidden = !d.tax || d.tax_inclusive;
        document.querySelector('[data-tax]').textContent = d.tax || '';
        document.getElementById('rowInclTax').hidden = !d.tax || !d.tax_inclusive;
        document.querySelector('[data-tax-incl]').textContent = d.tax || '';
        var msg = document.getElementById('couponMsg');
        if (d.coupon) { msg.innerHTML = '<span class="sp-tag"><i class="bi bi-tag"></i> ' + esc(d.coupon.code) + '</span>'; couponField.value = d.coupon.code; }
        else { msg.innerHTML = d.coupon_error ? '<span class="sp-err">' + esc(d.coupon_error) + '</span>' : ''; couponField.value = ''; }

        var box = document.getElementById('rates'), keep = d.rate_id || selectedRate();
        if (!d.rates.length) { box.innerHTML = '<div class="shck-empty">' + esc(T.enterCity) + '</div>'; toggleAddress(); return; }
        box.innerHTML = d.rates.map(function (r) {
            var chosen = String(keep) === String(r.id);
            return '<label class="ck-opt shck-opt"><input type="radio" name="shipping_rate_id" value="' + r.id + '" data-pickup="' + (r.pickup ? 1 : 0) + '"' + (chosen ? ' checked' : '') + '><span class="shck-radio"></span>' +
                '<span class="shck-ot"><b>' + esc(r.name) + '</b>' + (r.eta ? '<small>' + esc(r.eta) + '</small>' : '') + '</span><em class="figure">' + esc(r.price) + '</em></label>';
        }).join('');
        if (!$('input[name=shipping_rate_id]:checked')) { var f = $('input[name=shipping_rate_id]'); if (f) { f.checked = true; requote(); } }
        applyMode(); toggleAddress();
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
    form.addEventListener('change', function (e) {
        if (e.target.name === 'shipping_rate_id') { toggleAddress(); requote(); }
        if (e.target.name === 'delivery_mode') { applyMode(); toggleAddress(); }
        if (e.target.name === 'payment_method') payLabel();
    });
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
    applyMode(); toggleAddress(); payLabel();
})();
</script>
@endpush
