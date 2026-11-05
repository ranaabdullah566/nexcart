@extends('layouts.store')
@php $page = 'auth'; @endphp
@section('title', __('Sign in'))

@section('content')
<div class="sf-container py-3">
    <div class="mk-auth">
        @include('auth._aside', ['headline' => __('Welcome back'), 'sub' => __('Sign in to follow your orders, save your details and check out faster.')])

        <div class="mk-box mk-auth-card">
            <h1>{{ __('Sign in') }}</h1>
            <p class="mk-sub mb-3">{{ __('Sign in to your account') }}</p>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">{{ __('Email') }}</label>
                    <div class="mk-field"><i class="bi bi-envelope"></i><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" maxlength="190" class="form-control form-control-lg @error('email') is-invalid @enderror"></div>
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">{{ __('Password') }}</label>
                    <div class="mk-field"><i class="bi bi-lock"></i><input id="password" type="password" name="password" required autocomplete="current-password" maxlength="200" class="form-control form-control-lg @error('password') is-invalid @enderror"><button type="button" class="mk-eye" data-pw-toggle aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button></div>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <x-honeypot />
                <x-captcha />
                <label class="mk-check mb-3"><input type="checkbox" name="remember" value="1"><span>{{ __('Remember me') }}</span></label>
                <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-box-arrow-in-right me-1"></i> {{ __('Sign in') }}</button>
                @if (\App\Support\Integrations::google())
                    <div class="mk-or"><span>{{ __('or') }}</span></div>
                    <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary btn-lg w-100"><i class="bi bi-google me-2"></i> {{ __('Continue with Google') }}</a>
                @endif
            </form>
            <p class="mk-auth-switch">{{ __('New here?') }} <a href="{{ route('register') }}">{{ __('Create an account') }}</a></p>
        </div>
    </div>
</div>
@endsection
