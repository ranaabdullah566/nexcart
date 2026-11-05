@extends('layouts.store')
@php($page = 'cart')
@section('title', __('Your cart'))

@section('content')
<div class="sf-container py-4" id="sfCartPage">
    <h1 class="h2 mb-4">{{ __('Your cart') }}</h1>
    @if (empty($cart['lines']))
        <div class="sf-empty"><i class="bi bi-bag"></i><h2 class="h5">{{ __('Your cart is empty') }}</h2><a href="{{ route('store.shop') }}" class="btn btn-primary mt-3">{{ __('Start shopping') }}</a></div>
    @else
        <div class="row g-4">
            <div class="col-lg-8">
                @foreach ($cart['lines'] as $l)
                    <div class="sf-line" data-cart-row="{{ $l['key'] }}" style="grid-template-columns:84px 1fr auto">
                        @if ($l['image'])<img src="{{ $l['image'] }}" alt="" style="width:84px;height:84px">@else<span class="ph" style="width:84px;height:84px"><i class="bi bi-box-seam"></i></span>@endif
                        <div>
                            <a href="{{ $l['url'] }}">{{ $l['name'] }}</a>
                            @php($opts = collect([$l['variant']])->merge($l['options'])->filter()->implode(' · '))
                            @if ($opts)<small>{{ $opts }}</small>@endif
                            <small class="figure">{{ $l['unit'] }}</small>
                            @if ($l['short'])<small class="warn"><i class="bi bi-exclamation-triangle"></i> {{ __('Only :n left in stock.', ['n' => $l['available']]) }}</small>@endif
                            <div class="sf-mini-qty"><button type="button" data-page-qty="-1" aria-label="-">−</button><span class="q">{{ $l['qty'] }}</span><button type="button" data-page-qty="1" aria-label="+">+</button></div>
                        </div>
                        <div class="text-end"><div class="figure fw-bold lt">{{ $l['total'] }}</div><button type="button" class="rm mt-2" data-page-remove aria-label="{{ __('Remove') }}"><i class="bi bi-trash3"></i></button></div>
                    </div>
                @endforeach
            </div>
            <div class="col-lg-4">
                <div class="sf-summary">
                    <h2 class="h5 mb-3">{{ __('Order summary') }}</h2>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">{{ __('Subtotal') }}</span><b class="figure" data-page-subtotal>{{ $cart['subtotal'] }}</b></div>
                    <div class="small text-muted mb-3">{{ __('Shipping and tax are calculated at checkout.') }}</div>
                    @if (Route::has('store.checkout'))<a href="{{ route('store.checkout') }}" class="btn btn-primary btn-lg w-100"><i class="bi bi-lock-fill me-1"></i> {{ __('Checkout') }}</a>@endif
                    <a href="{{ route('store.shop') }}" class="sf-btn-ghost justify-content-center w-100 mt-2">{{ __('Continue shopping') }}</a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
