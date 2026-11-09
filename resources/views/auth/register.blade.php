@extends('layouts.store')
@php $page = 'auth'; @endphp
@section('title', __('Create account'))

@section('content')
<div class="sf-container py-3">
    <div class="mk-auth">
        @include('auth._aside', ['headline' => __('Create your account'), 'sub' => __('Join :store for faster checkout, order tracking and prescription history.', ['store' => setting('store.name')])])

        <div class="mk-box mk-auth-card">
            <h1>{{ __('Create your account') }}</h1>
            <p class="mk-sub mb-3">{{ setting('store.name') }}</p>
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3"><label class="form-label" for="rgName">{{ __('Full name') }} <span class="text-danger">*</span></label>
                    <div class="mk-field"><i class="bi bi-person"></i><input id="rgName" name="name" value="{{ old('name', session('guest_prefill.name')) }}" required autofocus autocomplete="name" maxlength="120" class="form-control form-control-lg @error('name') is-invalid @enderror"></div>@error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-7"><label class="form-label" for="rgMail">{{ __('Email') }} <span class="text-danger">*</span></label>
                        <div class="mk-field"><i class="bi bi-envelope"></i><input id="rgMail" type="email" name="email" value="{{ old('email', session('guest_prefill.email')) }}" required autocomplete="username" maxlength="190" class="form-control form-control-lg @error('email') is-invalid @enderror"></div>@error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                    <div class="col-md-5"><label class="form-label" for="rgPhone">{{ __('Phone') }}</label>
                        <div class="mk-field"><i class="bi bi-telephone"></i><input id="rgPhone" name="phone" value="{{ old('phone', session('guest_prefill.phone')) }}" autocomplete="tel" inputmode="tel" maxlength="40" pattern="[0-9+\-()\s]{6,}" data-msg-pattern="{{ __('Enter a valid phone number.') }}" class="form-control form-control-lg @error('phone') is-invalid @enderror"></div>@error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                </div>
                <div class="mb-2"><label class="form-label" for="rgPass">{{ __('Password') }} <span class="text-danger">*</span></label>
                    <div class="mk-field"><i class="bi bi-lock"></i><input id="rgPass" type="password" name="password" required minlength="10" autocomplete="new-password" data-strength="#pwRules" class="form-control form-control-lg @error('password') is-invalid @enderror"><button type="button" class="mk-eye" data-pw-toggle aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button></div>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                <ul class="mk-rules" id="pwRules" aria-live="polite">
                    <li data-rule="len"><i class="bi bi-circle"></i> {{ __('At least 10 characters') }}</li>
                    <li data-rule="letter"><i class="bi bi-circle"></i> {{ __('A letter') }}</li>
                    <li data-rule="digit"><i class="bi bi-circle"></i> {{ __('A number') }}</li>
                </ul>
                <div class="mb-3"><label class="form-label" for="rgPass2">{{ __('Confirm password') }} <span class="text-danger">*</span></label>
                    <div class="mk-field"><i class="bi bi-shield-lock"></i><input id="rgPass2" type="password" name="password_confirmation" required autocomplete="new-password" class="form-control form-control-lg"><button type="button" class="mk-eye" data-pw-toggle aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button></div></div>
                <x-honeypot />
                <x-captcha />
                <button class="btn btn-primary btn-lg w-100"><i class="bi bi-person-plus me-1"></i> {{ __('Create account') }}</button>
                @if (\App\Support\Integrations::google())
                    <div class="mk-or"><span>{{ __('or') }}</span></div>
                    <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary btn-lg w-100"><i class="bi bi-google me-2"></i> {{ __('Continue with Google') }}</a>
                @endif
            </form>
            <p class="mk-auth-switch">{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a></p>
            <a href="{{ route('store.shop') }}" class="tq-guest">{{ __('Browse as guest') }} <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</div>
@endsection
