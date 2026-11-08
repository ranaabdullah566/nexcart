@extends('layouts.store')
@php $page = 'cart'; @endphp
@section('title', __('Your cart'))

@section('content')
<div class="sf-container py-3" id="sfCartPage">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><span>{{ __('Your cart') }}</span></nav>
    <div class="mk-page-head"><h1>{{ __('Your cart') }}</h1>@if (! empty($cart['lines']))<span class="mk-count-chip">{{ trans_choice(':n item|:n items', $cart['count'], ['n' => $cart['count']]) }}</span>@endif</div>

    @if (empty($cart['lines']))
        <div class="mk-empty"><span><i class="bi bi-bag"></i></span><h2>{{ __('Your cart is empty') }}</h2><p>{{ __('Looks like you have not added anything yet.') }}</p><a href="{{ route('store.shop') }}" class="btn btn-primary btn-lg">{{ __('Start shopping') }}</a></div>
    @else
        <div class="mk-cart">
            <div class="mk-cart-lines">
                @if ($freeOver)
                    @php $have = (float) $cart['subtotal_raw']; $left = max(0, $freeOver - $have); @endphp
                    <div class="mk-freeship {{ $left <= 0 ? 'done' : '' }}" data-free-ship="{{ $freeOver }}" data-done-text="{{ __('You get free delivery!') }}" data-more-text="{{ __('Add :amount more for free delivery', ['amount' => ':amount']) }}">
                        <i class="bi bi-truck"></i><div><b data-free-msg>{{ $left <= 0 ? __('You get free delivery!') : __('Add :amount more for free delivery', ['amount' => money($left)]) }}</b><div class="bar"><i style="width:{{ min(100, $have / $freeOver * 100) }}%"></i></div></div>
                    </div>
                @endif
                @foreach ($cart['lines'] as $l)
                    <article class="mk-line sf-line" data-cart-row="{{ $l['key'] }}">
                        <a href="{{ $l['url'] }}" class="mk-line-img">@if ($l['image'])<img src="{{ $l['image'] }}" alt="" loading="lazy">@else<i class="bi bi-box-seam"></i>@endif</a>
                        <div class="mk-line-info">
                            <a href="{{ $l['url'] }}" class="mk-line-name">{{ $l['name'] }}</a>
                            @php $opts = $l['variant']; @endphp
                            @if ($opts)<small>{{ $opts }}</small>@endif
                            <small class="figure">{{ $l['unit'] }} {{ __('each') }}</small>
                            @if ($l['short'])<small class="warn"><i class="bi bi-exclamation-triangle"></i> {{ __('Only :n left in stock.', ['n' => $l['available']]) }}</small>@endif
                            <div class="mk-line-actions">
                                <div class="mk-stepper sf-mini-qty"><button type="button" data-page-qty="-1" aria-label="{{ __('Less') }}">−</button><span class="q">{{ $l['qty'] }}</span><button type="button" data-page-qty="1" aria-label="{{ __('More') }}">+</button></div>
                                <button type="button" class="mk-line-del" data-page-remove><i class="bi bi-trash3"></i> {{ __('Remove') }}</button>
                            </div>
                        </div>
                        <div class="mk-line-total figure lt">{{ $l['total'] }}</div>
                    </article>
                @endforeach
                <a href="{{ route('store.shop') }}" class="mk-continue"><i class="bi bi-arrow-left"></i> {{ __('Continue shopping') }}</a>
            </div>

            <aside class="mk-summary">
                <h2>{{ __('Order summary') }}</h2>
                <div class="mk-sum-row"><span>{{ __('Subtotal') }}</span><b class="figure" data-page-subtotal>{{ $cart['subtotal'] }}</b></div>
                <div class="mk-sum-row muted"><span>{{ __('Shipping') }}</span><span>{{ __('At checkout') }}</span></div>
                <div class="mk-sum-row muted"><span>{{ __('Tax') }}</span><span>{{ __('At checkout') }}</span></div>
                <p class="mk-sum-note"><i class="bi bi-ticket-perforated"></i> {{ __('Have a discount code? You can enter it at checkout.') }}</p>
                @if (Route::has('store.checkout'))<a href="{{ route('store.checkout') }}" class="btn btn-primary btn-lg w-100"><i class="bi bi-lock-fill me-1"></i> {{ __('Checkout') }}</a>@endif
                <ul class="mk-sum-trust">
                    <li><i class="bi bi-shield-check"></i> {{ __('Secure checkout') }}</li>
                    <li><i class="bi bi-truck"></i> {{ __('Delivery to your city') }}</li>
                    <li><i class="bi bi-arrow-counterclockwise"></i> {{ __('Easy returns') }}</li>
                </ul>
            </aside>
        </div>
    @endif
</div>
@endsection
