@extends('layouts.store')
@php $page = 'auth'; @endphp
@section('title', __('Forgot password'))

@section('content')
<div class="sf-container py-3">
    <div class="mk-auth mk-auth-solo">
        <div class="mk-box mk-auth-card">
            <span class="mk-success-ic mb-3"><i class="bi bi-key"></i></span>
            <h1>{{ __('Forgot your password?') }}</h1>
            <p class="mk-sub mb-3">{{ __('Enter the e-mail you signed up with and we will send you a link to choose a new one.') }}</p>

            @if (session('success'))
                <div class="mk-note mb-3" role="status"><i class="bi bi-envelope-check"></i> {{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="fpMail">{{ __('Email') }}</label>
                    <div class="mk-field"><i class="bi bi-envelope"></i><input id="fpMail" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" maxlength="190" class="form-control form-control-lg @error('email') is-invalid @enderror"></div>
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <x-honeypot />
                <x-captcha />
                <button class="btn btn-primary btn-lg w-100"><i class="bi bi-send me-1"></i> {{ __('Send reset link') }}</button>
            </form>
            <p class="mk-auth-switch"><a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> {{ __('Back to sign in') }}</a></p>
        </div>
    </div>
</div>
@endsection
