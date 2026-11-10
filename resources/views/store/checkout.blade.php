@extends('layouts.checkout')
@php $page = 'checkout'; @endphp
@section('title', __('Checkout'))

@section('content')
@php $storeName = setting('store.name', config('app.name')); $footerMenu = \App\Models\MenuItem::resolved('footer'); @endphp
<form method="POST" action="{{ route('store.checkout.place') }}" enctype="multipart/form-data" id="ckForm" class="shck" novalidate>
    @csrf
    <header class="shck-head">
        <a href="{{ route('store.home') }}" class="shck-brand" aria-label="{{ $storeName }}"><span class="shck-logo"><img src="{{ brand_logo() }}" alt=""></span><span>{{ $storeName }}</span></a>
        <a href="{{ route('store.cart') }}" class="shck-bag d-lg-none" aria-label="{{ __('Your cart') }}"><i class="bi bi-bag"></i></a>
    </header>

    {{-- ============ left: the form ============ --}}
    <div class="shck-main">
        <nav class="shck-crumbs" aria-label="{{ __('Checkout steps') }}">
            <a href="{{ route('store.cart') }}">{{ __('Cart') }}</a><i class="bi bi-chevron-right"></i>
            <a href="#ckContact" class="on" aria-current="step">{{ __('Information') }}</a><i class="bi bi-chevron-right"></i>
            <a href="#ckShip">{{ __('Shipping') }}</a><i class="bi bi-chevron-right"></i>
            <a href="#ckPay">{{ __('Payment') }}</a>
        </nav>

        @if ($errors->any())
            <div class="shck-alert" role="alert"><i class="bi bi-exclamation-circle-fill"></i><div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
        @endif

        <section class="shck-sec" id="ckContact">
            <div class="shck-h"><h2>{{ __('Contact') }}</h2>@guest<a href="{{ route('login') }}">{{ __('Sign in') }}</a>@endguest</div>
            <div class="shf"><input id="ckEmail" type="email" name="email" value="{{ old('email', $defaults['email']) }}" placeholder=" " required maxlength="190" autocomplete="email" class="@error('email') is-invalid @enderror"><label for="ckEmail">{{ __('Email') }}</label>@error('email')<div class="shf-err">{{ $message }}</div>@enderror</div>
            @guest<p class="shck-note">{{ __('No account needed — you are checking out as a guest. You can create an account after ordering.') }}</p>@endguest
        </section>

        <section class="shck-sec" id="ckDelivery">
            <div class="shck-h"><h2>{{ __('Delivery') }}</h2></div>
            <div class="shck-grid" id="addrBox">
                <div class="shf g12"><input id="ckName" name="name" value="{{ old('name', $defaults['name']) }}" placeholder=" " required maxlength="120" autocomplete="name" class="@error('name') is-invalid @enderror"><label for="ckName">{{ __('Full name') }}</label>@error('name')<div class="shf-err">{{ $message }}</div>@enderror</div>
                <div class="shf g12 addr-only"><input id="ckAddr" name="address_line" value="{{ old('address_line', $defaults['address_line']) }}" placeholder=" " maxlength="255" autocomplete="street-address" class="@error('address_line') is-invalid @enderror"><label for="ckAddr">{{ __('Address') }}</label>@error('address_line')<div class="shf-err">{{ $message }}</div>@enderror</div>
                @if (sf('checkout.show_area'))<div class="shf g12 addr-only"><input id="ckArea" name="area" value="{{ old('area', $defaults['area']) }}" placeholder=" " maxlength="120" autocomplete="address-line2"><label for="ckArea">{{ __('Area') }} ({{ __('optional') }})</label></div>@endif
                <div class="shf g6 addr-only"><input id="ckCity" name="city" value="{{ old('city', $defaults['city']) }}" placeholder=" " maxlength="120" list="ckCities" autocomplete="address-level2" class="@error('city') is-invalid @enderror"><label for="ckCity">{{ __('City') }}</label><datalist id="ckCities">@foreach ($cities as $c)<option value="{{ $c }}">@endforeach</datalist>@error('city')<div class="shf-err">{{ $message }}</div>@enderror</div>
                @if (sf('checkout.show_postal'))<div class="shf g6 addr-only"><input id="ckPostal" name="postal_code" value="{{ old('postal_code', $defaults['postal_code']) }}" placeholder=" " maxlength="20" autocomplete="postal-code"><label for="ckPostal">{{ __('Postal code') }} ({{ __('optional') }})</label></div>@endif
                <div class="shf g12"><input id="ckPhone" type="tel" name="phone" value="{{ old('phone', $defaults['phone']) }}" placeholder=" " required autocomplete="tel" inputmode="tel" maxlength="40" pattern="[0-9+\-()\s]{6,}" title="{{ __('Digits, spaces, + - ( ) only') }}" class="@error('phone') is-invalid @enderror"><label for="ckPhone">{{ __('Phone') }}</label>@error('phone')<div class="shf-err">{{ $message }}</div>@enderror</div>
            </div>
        </section>

        <section class="shck-sec" id="ckShip">
            <div class="shck-h"><h2>{{ __('Shipping method') }}</h2></div>
            <div id="rates" class="shck-group" role="radiogroup" aria-label="{{ __('Shipping method') }}">
                @forelse ($q['rates'] as $r)
                    <label class="ck-opt"><input type="radio" name="shipping_rate_id" value="{{ $r->id }}" data-pickup="{{ $r->is_pickup ? 1 : 0 }}" @checked($q['rate']?->id === $r->id)>
                        <span><b>{{ $r->name }}</b>@if ($r->eta)<small>{{ $r->eta }}</small>@endif</span>
                        <em class="figure">{{ $r->costFor($q['subtotal']) > 0 ? money($r->costFor($q['subtotal'])) : __('Free') }}</em></label>
                @empty
                    <div class="shck-empty" id="noRates">{{ __('Enter your city to see delivery options.') }}</div>
                @endforelse
            </div>
            @error('shipping_rate_id')<div class="shf-err">{{ $message }}</div>@enderror
        </section>

                @if ($slotDays)
                    <section class="shck-sec" id="slotSec">
                        <div class="shck-h"><h2>{{ __('Delivery time') }}</h2></div>
                        <div class="sf-pills mb-3" id="slotDays" role="radiogroup" aria-label="{{ __('Delivery day') }}">
                            @foreach ($slotDays as $i => $d)
                                <label class="sf-pill"><input type="radio" name="slot_date" value="{{ $d['date'] }}" @checked(old('slot_date', collect($slotDays)->first(fn ($x) => collect($x['slots'])->contains('open', true))['date'] ?? $slotDays[0]['date']) === $d['date'])><span>{{ $d['label'] }}</span></label>
                            @endforeach
                        </div>
                        @foreach ($slotDays as $d)
                            <div class="shck-group slot-day" data-date="{{ $d['date'] }}" hidden>
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
                    <section class="shck-sec">
                        <div class="shck-h"><h2>{{ __('Prescription') }}</h2></div>
                        <p class="text-muted small">{{ __('Your cart has items that need a prescription. Upload a clear photo or PDF — our pharmacist will verify it before dispatch.') }}</p>
                        <input type="file" name="prescription[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf" class="form-control @error('prescription') is-invalid @enderror @error('prescription.*') is-invalid @enderror" required>
                        @error('prescription')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @error('prescription.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <label class="ck-opt"><input type="radio" name="payment_method" value="{{ $key }}" @checked(old('payment_method', array_key_first($gateways)) === $key) required>
                                <span><b>{{ $g->label() }}</b><small>{{ $g->description() }}</small></span>
                                <i class="bi bi-{{ ['cod' => 'cash-coin', 'bank' => 'bank', 'stripe' => 'credit-card-2-front'][$key] ?? 'wallet2' }}"></i></label>
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
                <div class="shck-actions">
                    <a href="{{ route('store.cart') }}" class="shck-back"><i class="bi bi-chevron-left"></i> {{ __('Return to cart') }}</a>
                    <button type="submit" class="shck-pay" id="placeBtn" @disabled(empty($gateways))>{{ __('Complete order') }}</button>
                </div>
    </div>

    <footer class="shck-foot">
        <nav aria-label="{{ __('Legal') }}">@foreach ($footerMenu->take(6) as $m)<a href="{{ $m['href'] }}">{{ $m['label'] }}</a>@endforeach</nav>
    </footer>

    {{-- ============ right: order summary ============ --}}
    <div class="shck-side">
                @php
                    $itemCount = collect($lines['lines'])->sum('qty');
                    $cur = strtoupper((string) setting('store.currency', 'PKR'));
                @endphp
                <aside class="sp" aria-label="{{ __('Order summary') }}">
                    {{-- phones: Shopify's collapsed "Show order summary" bar --}}
                    <button type="button" class="sp-toggle d-lg-none" data-bs-toggle="collapse" data-bs-target="#spBody" aria-expanded="false" aria-controls="spBody">
                        <span><i class="bi bi-cart3"></i> <span class="sp-show">{{ __('Show order summary') }}</span><span class="sp-hide">{{ __('Hide order summary') }}</span> <i class="bi bi-chevron-down sp-chev"></i></span>
                        <b data-total>{{ money($q['total']) }}</b>
                    </button>
                    <div id="spBody" class="sp-body collapse d-lg-block">
                        <div class="sp-lines">
                            @foreach ($lines['lines'] as $l)
                                <div class="sp-line">
                                    <div class="sp-thumb">@if ($l['image'])<img src="{{ $l['image'] }}" alt="">@else<i class="bi bi-box-seam"></i>@endif<span class="sp-qty" aria-label="{{ __('Quantity') }}">{{ $l['qty'] }}</span></div>
                                    <div class="sp-name"><b>{{ $l['name'] }}</b>@php($o = $l['variant'])@if ($o)<small>{{ $o }}</small>@endif</div>
                                    <div class="sp-price">{{ $l['total'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="sp-code">
                            <input type="text" id="couponInput" maxlength="40" placeholder="{{ __('Discount code') }}" value="{{ session('coupon') }}" autocomplete="off" aria-label="{{ __('Discount code') }}">
                            <button type="button" id="couponBtn">{{ __('Apply') }}</button>
                        </div>
                        <div class="sp-codemsg" id="couponMsg" role="status">@if ($q['coupon'] && ! $q['coupon_problem'])<span class="sp-tag"><i class="bi bi-tag-fill"></i> {{ $q['coupon']->code }}</span> <small>{{ $q['coupon']->label() }}</small>@endif</div>

                        <dl class="sp-rows">
                            <div class="sp-row"><dt>{{ __('Subtotal') }} <span class="sp-dim">· {{ trans_choice(':n item|:n items', $itemCount, ['n' => $itemCount]) }}</span></dt><dd data-sub>{{ money($q['subtotal']) }}</dd></div>
                            <div class="sp-row" id="rowDiscount" @if ($q['discount'] <= 0) hidden @endif><dt>{{ __('Discount') }}</dt><dd class="sp-good" data-discount>−{{ money($q['discount']) }}</dd></div>
                            <div class="sp-row"><dt>{{ __('Shipping') }}</dt><dd data-ship class="@unless ($q['rate']) sp-dim @endunless">{{ $q['rate'] ? ($q['shipping'] > 0 ? money($q['shipping']) : __('Free')) : __('Enter shipping address') }}</dd></div>
                            <div class="sp-row" id="rowTax" @if ($q['tax'] <= 0 || $q['tax_inclusive']) hidden @endif><dt>{{ __('Estimated taxes') }}</dt><dd data-tax>{{ money($q['tax']) }}</dd></div>
                        </dl>
                        <div class="sp-total"><span>{{ __('Total') }}</span><span class="sp-totalamt"><small>{{ $cur }}</small> <b data-total>{{ money($q['total']) }}</b></span></div>
                        <p class="sp-incl" id="rowInclTax" @if ($q['tax'] <= 0 || ! $q['tax_inclusive']) hidden @endif>{{ __('Including') }} <span data-tax-incl>{{ money($q['tax']) }}</span> {{ __('in taxes') }}</p>
                        <p class="sp-save" id="rowSave" @if ($q['discount'] <= 0) hidden @endif><i class="bi bi-tag"></i> {{ __('Total savings') }} <b data-save>{{ money($q['discount']) }}</b></p>

                        <ul class="sp-trust">
                            <li><i class="bi bi-shield-lock"></i> {{ __('Secure checkout') }}</li>
                            <li><i class="bi bi-truck"></i> {{ __('Delivery to your city') }}</li>
                            <li><i class="bi bi-arrow-counterclockwise"></i> {{ __('Easy returns') }}</li>
                        </ul>
                    </div>
                </aside>
    </div>
</form>
@endsection

@push('head')
<style>.slot-day[hidden] { display: none !important; }</style>
@endpush

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    var form = document.getElementById('ckForm'), csrf = document.querySelector('meta[name=csrf-token]').content;
    var $ = function (s) { return form.querySelector(s); }, T = {{ \Illuminate\Support\Js::from(['enterCity' => __('Enter your city to see delivery options.'), 'free' => __('Free'), 'applied' => __('Applied'), 'enterShip' => __('Enter shipping address')]) }};
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
        var rd = document.getElementById('rowDiscount'); rd.hidden = !d.discount;
        document.querySelector('[data-discount]').textContent = d.discount || '';
        var sv = document.getElementById('rowSave'); sv.hidden = !d.discount; document.querySelector('[data-save]').textContent = d.discount ? d.discount.replace('\u2212', '') : '';
        var sh = document.querySelector('[data-ship]'); sh.textContent = d.shipping || T.enterShip; sh.classList.toggle('sp-dim', !d.shipping);
        document.getElementById('rowTax').hidden = !d.tax || d.tax_inclusive;
        document.querySelector('[data-tax]').textContent = d.tax || '';
        document.getElementById('rowInclTax').hidden = !d.tax || !d.tax_inclusive;
        document.querySelector('[data-tax-incl]').textContent = d.tax || '';
        var msg = document.getElementById('couponMsg');
        if (d.coupon) { msg.innerHTML = '<span class="sp-tag"><i class="bi bi-tag-fill"></i> ' + esc(d.coupon.code) + '</span> <small>' + esc(d.coupon.label) + '</small>'; couponField.value = d.coupon.code; }
        else { msg.innerHTML = d.coupon_error ? '<span class="sp-err"><i class="bi bi-exclamation-circle"></i> ' + esc(d.coupon_error) + '</span>' : ''; couponField.value = ''; }

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
