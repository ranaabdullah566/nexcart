@extends('layouts.store')
@php $page = 'cart'; @endphp
@section('title', __('Your cart'))

@section('content')
<div class="sf-container cx py-3" id="sfCartPage">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><span>{{ __('Your cart') }}</span></nav>

    <header class="cx-hero">
        <div class="cx-hero-t">
            <h1>{{ __('Your cart') }}</h1>
            @if (! empty($cart['lines']))<span class="cx-chip" data-page-count>{{ trans_choice(':n item|:n items', $cart['count'], ['n' => $cart['count']]) }}</span>@endif
        </div>
        <ol class="cx-steps" aria-label="{{ __('Checkout steps') }}">
            <li class="now"><i class="bi bi-bag"></i><span>{{ __('Cart') }}</span></li>
            <li><i class="bi bi-credit-card-2-front"></i><span>{{ __('Details & payment') }}</span></li>
            <li><i class="bi bi-check2-circle"></i><span>{{ __('Done') }}</span></li>
        </ol>
    </header>

    @if (empty($cart['lines']))
        <div class="cx-empty">
            <span class="cx-orb"><i class="bi bi-bag"></i></span>
            <h2>{{ __('Your cart is empty') }}</h2>
            <p>{{ __('Looks like you have not added anything yet.') }}</p>
            <div class="cx-empty-cta"><a href="{{ route('store.shop') }}" class="cx-btn">{{ __('Start shopping') }} <i class="bi bi-arrow-right"></i></a>
                @if (\App\Support\BusinessProfile::hasModule('prescriptions'))<a href="{{ route('store.prescription.upload') }}" class="cx-btn ghost"><i class="bi bi-camera"></i> {{ __('Upload prescription') }}</a>@endif</div>
        </div>
    @else
        <div class="cx-grid">
            <div class="cx-lines">
                @if ($freeOver)
                    @php $have = (float) $cart['subtotal_raw']; $left = max(0, $freeOver - $have); @endphp
                    <div class="cx-ship mk-freeship {{ $left <= 0 ? 'done' : '' }}" data-free-ship="{{ $freeOver }}" data-done-text="{{ __('You get free delivery!') }}" data-more-text="{{ __('Add :amount more for free delivery', ['amount' => ':amount']) }}">
                        <span class="cx-ship-ic"><i class="bi bi-truck"></i></span>
                        <div class="cx-ship-b"><b data-free-msg>{{ $left <= 0 ? __('You get free delivery!') : __('Add :amount more for free delivery', ['amount' => money($left)]) }}</b>
                            <div class="bar"><i style="width:{{ min(100, $have / $freeOver * 100) }}%"></i></div></div>
                    </div>
                @endif

                @foreach ($cart['lines'] as $l)
                    <article class="cx-line mk-line sf-line" data-cart-row="{{ $l['key'] }}" style="--i:{{ $loop->index }}">
                        <a href="{{ $l['url'] }}" class="cx-img" aria-label="{{ $l['name'] }}">@if ($l['image'])<img src="{{ $l['image'] }}" alt="" loading="lazy">@else<i class="bi bi-box-seam"></i>@endif @if ($l['rx'])<em>Rx</em>@endif</a>
                        <div class="cx-info">
                            <a href="{{ $l['url'] }}" class="cx-name">{{ $l['name'] }}</a>
                            @if ($l['variant'])<small class="cx-var">{{ $l['variant'] }}</small>@endif
                            <small class="cx-unit">{{ $l['unit'] }} {{ __('each') }}</small>
                            @if ($l['short'])<small class="cx-warn"><i class="bi bi-exclamation-triangle"></i> {{ __('Only :n left in stock.', ['n' => $l['available']]) }}</small>@endif
                            <div class="cx-actions">
                                <div class="cx-step" role="group" aria-label="{{ __('Quantity') }}"><button type="button" data-page-qty="-1" aria-label="{{ __('Less') }}">−</button><span class="q">{{ $l['qty'] }}</span><button type="button" data-page-qty="1" aria-label="{{ __('More') }}">+</button></div>
                                <button type="button" class="cx-link" data-page-save="{{ $l['wish'] }}"><i class="bi bi-heart"></i> {{ __('Save for later') }}</button>
                                <button type="button" class="cx-link danger" data-page-remove><i class="bi bi-trash3"></i> {{ __('Remove') }}</button>
                            </div>
                        </div>
                        <div class="cx-total"><b class="lt">{{ $l['total'] }}</b>@if ($l['was'])<s>{{ $l['was'] }}</s>@endif</div>
                    </article>
                @endforeach
                <a href="{{ route('store.shop') }}" class="cx-continue"><i class="bi bi-arrow-left"></i> {{ __('Continue shopping') }}</a>
            </div>

            <aside class="cx-sum" aria-label="{{ __('Order summary') }}">
                <div class="cx-sum-glow" aria-hidden="true"></div>
                <h2>{{ __('Order summary') }}</h2>
                <div class="cx-row"><span>{{ __('Subtotal') }} <small data-page-count2>· {{ trans_choice(':n item|:n items', $cart['count'], ['n' => $cart['count']]) }}</small></span><b data-page-subtotal>{{ $cart['subtotal'] }}</b></div>
                <div class="cx-row cx-good" data-page-saverow @if (! $cart['savings']) hidden @endif><span><i class="bi bi-stars"></i> {{ __('You save') }}</span><b data-page-save-amt>{{ $cart['savings'] }}</b></div>
                <div class="cx-row cx-good" id="cxDiscRow" hidden><span><i class="bi bi-tag-fill"></i> <span id="cxDiscCode">{{ __('Discount') }}</span></span><b id="cxDisc"></b></div>
                <div class="cx-row muted"><span>{{ __('Shipping') }}</span><span>{{ __('At checkout') }}</span></div>
                <div class="cx-row muted"><span>{{ __('Tax') }}</span><span>{{ __('At checkout') }}</span></div>

                <form class="cx-code" id="cxCode" data-no-validate data-url="{{ route('store.checkout.quote') }}" autocomplete="off">
                    <label class="visually-hidden" for="cxCodeIn">{{ __('Discount code') }}</label>
                    <i class="bi bi-ticket-perforated"></i>
                    <input type="text" id="cxCodeIn" maxlength="40" placeholder="{{ __('Discount code') }}" value="{{ session('coupon') }}">
                    <button type="submit">{{ __('Apply') }}</button>
                </form>
                <div class="cx-code-msg" id="cxCodeMsg" role="status"></div>

                <div class="cx-est"><span>{{ __('Estimated total') }}</span><b id="cxEst" data-est-base="{{ $cart['subtotal_raw'] }}">{{ $cart['subtotal'] }}</b></div>
                @if (Route::has('store.checkout'))<a href="{{ route('store.checkout') }}" class="cx-go"><i class="bi bi-lock-fill"></i> {{ __('Checkout') }} <i class="bi bi-arrow-right"></i></a>@endif
                <ul class="cx-trust">
                    <li><i class="bi bi-shield-check"></i> {{ __('Secure checkout') }}</li>
                    <li><i class="bi bi-patch-check"></i> {{ __('Genuine medicines') }}</li>
                    <li><i class="bi bi-truck"></i> {{ __('Delivery to your city') }}</li>
                    <li><i class="bi bi-arrow-counterclockwise"></i> {{ __('Easy returns') }}</li>
                </ul>
            </aside>
        </div>
    @endif

    @if ($recommended->isNotEmpty())
    <section class="sf-section" data-rail>
        <div class="sf-section-head"><div><h2>{{ __('Recommended for you') }}</h2><p class="mk-sub">{{ __('Popular with people who buy these medicines.') }}</p></div>@include('store._rail-ctrl')</div>
        <div class="tq-rail tq-rail-5" data-rail-track>@foreach ($recommended as $p)<div class="tq-rail-item">@include('store._card', ['p' => $p])</div>@endforeach</div>
    </section>
    @endif
    @include('store._recent')
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    var f = document.getElementById('cxCode'); if (!f) return;
    var inp = document.getElementById('cxCodeIn'), msg = document.getElementById('cxCodeMsg'), row = document.getElementById('cxDiscRow'), est = document.getElementById('cxEst');
    var csrf = document.querySelector('meta[name=csrf-token]').content;
    function money(n) { return window.SF ? SF.money(n) : n.toFixed(2); }
    function base() { return (window.SF && SF.cart ? SF.cart.subtotal_raw : parseFloat(est.dataset.estBase)) || 0; }
    var disc = 0;
    function paint() { row.hidden = !disc; document.getElementById('cxDisc').textContent = disc ? '−' + money(disc) : ''; est.textContent = money(Math.max(0, base() - disc)); }
    function apply(code, silent) {
        fetch(f.dataset.url, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ coupon: code || null }) })
            .then(function (r) { return r.json(); }).then(function (d) {
                disc = d.coupon ? (d.discount_raw || 0) : 0;
                if (d.coupon) { msg.className = 'cx-code-msg ok'; msg.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + d.coupon.code + ' · ' + d.coupon.label; document.getElementById('cxDiscCode').textContent = d.coupon.code; }
                else { msg.className = 'cx-code-msg bad'; msg.textContent = silent ? '' : (d.coupon_error || ''); }
                paint();
            }).catch(function () {});
    }
    f.addEventListener('submit', function (e) { e.preventDefault(); if (inp.value.trim()) apply(inp.value.trim()); });
    if (inp.value.trim()) apply(inp.value.trim(), true);
    var prev = window.SF && SF.cartPageHook; if (window.SF) SF.cartPageHook = function (c) { if (prev) prev(c); paint(); };
})();
</script>
@endpush
