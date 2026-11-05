@extends('layouts.guest')
@section('title', __('Sign in'))
@section('card_class', 'narrow')

@section('content')
<div class="text-center mb-4">
    <span class="nx-brand-logo mx-auto mb-3" style="width:56px;height:56px"><img src="{{ brand_logo() }}" alt="" style="width:100%;height:100%"></span>
    <h1 class="nx-title">{{ setting('store.name') }}</h1>
    <p class="nx-sub">{{ __('Sign in to your account') }}</p>
</div>

<div class="card nx-form-card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-medium" for="email">{{ __('Email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="form-control @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-medium" for="password">{{ __('Password') }}</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="form-control">
            </div>
            <x-honeypot />
            <x-captcha />
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                <label class="form-check-label" for="remember">{{ __('Remember me') }}</label>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i> {{ __('Sign in') }}</button>
            @if (\App\Support\Integrations::google())
                <div class="text-center text-muted small my-3">{{ __('or') }}</div>
                <a href="{{ route('auth.google') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-google me-2"></i> {{ __('Continue with Google') }}</a>
            @endif
        </form>
        <div class="text-center mt-3 small">{{ __('New here?') }} <a href="{{ route('register') }}">{{ __('Create an account') }}</a> · <a href="{{ route('store.home') }}">{{ __('Back to store') }}</a></div>
    </div>
</div>
@endsection
