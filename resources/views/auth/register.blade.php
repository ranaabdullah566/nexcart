@extends('layouts.guest')
@section('title', __('Create account'))
@section('card_class', 'narrow')

@section('content')
<div class="text-center mb-4">
    <a href="{{ route('store.home') }}"><span class="nx-brand-logo mx-auto mb-3" style="width:56px;height:56px"><img src="{{ brand_logo() }}" alt="" style="width:100%;height:100%"></span></a>
    <h1 class="nx-title">{{ __('Create your account') }}</h1>
    <p class="nx-sub">{{ setting('store.name') }}</p>
</div>
<div class="card nx-form-card"><div class="card-body p-4">
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="mb-3"><label class="form-label fw-medium">{{ __('Full name') }}</label><input name="name" value="{{ old('name') }}" required autofocus class="form-control @error('name') is-invalid @enderror">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label class="form-label fw-medium">{{ __('Email') }}</label><input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="form-control @error('email') is-invalid @enderror">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label class="form-label fw-medium">{{ __('Phone') }}</label><input name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label class="form-label fw-medium">{{ __('Password') }}</label><input type="password" name="password" required minlength="8" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-4"><label class="form-label fw-medium">{{ __('Confirm password') }}</label><input type="password" name="password_confirmation" required autocomplete="new-password" class="form-control"></div>
        <x-honeypot />
        <x-captcha />
        <button class="btn btn-primary w-100"><i class="bi bi-person-plus me-1"></i> {{ __('Create account') }}</button>
    </form>
    <div class="text-center mt-3 small">{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a></div>
</div></div>
@endsection
