@extends('layouts.store')
@php $page = 'track'; @endphp
@section('title', __('Track an order'))

@section('content')
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><span>{{ __('Track an order') }}</span></nav>
    <div class="mk-track">
        <div class="mk-track-intro">
            <span class="mk-success-ic"><i class="bi bi-geo-alt"></i></span>
            <h1>{{ __('Track an order') }}</h1>
            <p>{{ __('Enter your order number and the email you used at checkout.') }}</p>
            <ul class="mk-sum-trust">
                <li><i class="bi bi-envelope"></i> {{ __('The order number is in your confirmation email.') }}</li>
                <li><i class="bi bi-shield-check"></i> {{ __('Only you can see your order — we check your email.') }}</li>
                <li><i class="bi bi-truck"></i> {{ __('See every step from packing to delivery.') }}</li>
            </ul>
        </div>
        <form method="POST" action="{{ route('store.track.find') }}" class="mk-box mk-track-form">
            @csrf
            <div class="mb-3"><label class="form-label" for="trNum">{{ __('Order number') }}</label><input id="trNum" name="number" value="{{ old('number') }}" required class="form-control form-control-lg figure @error('number') is-invalid @enderror" placeholder="BUR-100001" autocomplete="off">@error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label" for="trMail">{{ __('Email') }}</label><input id="trMail" type="email" name="email" value="{{ old('email') }}" required class="form-control form-control-lg @error('email') is-invalid @enderror" autocomplete="email">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <x-honeypot />
            <x-captcha />
            <button class="btn btn-primary btn-lg w-100"><i class="bi bi-search me-1"></i> {{ __('Find my order') }}</button>
            @auth<a href="{{ route('store.account') }}" class="d-block text-center mt-3 small fw-bold">{{ __('Or open all your orders in My account') }}</a>@endauth
        </form>
    </div>
</div>
@endsection
