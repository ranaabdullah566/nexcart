@extends('layouts.store')
@php $page = 'auth'; @endphp
@section('title', __('Choose a new password'))

@section('content')
<div class="sf-container py-3">
    <div class="mk-auth mk-auth-solo">
        <div class="mk-box mk-auth-card">
            <span class="mk-success-ic mb-3"><i class="bi bi-shield-lock"></i></span>
            <h1>{{ __('Choose a new password') }}</h1>
            <p class="mk-sub mb-3">{{ __('Pick something only you know. You will be signed out on every device.') }}</p>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-3">
                    <label class="form-label" for="rpMail">{{ __('Email') }}</label>
                    <div class="mk-field"><i class="bi bi-envelope"></i><input id="rpMail" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username" maxlength="190" class="form-control form-control-lg @error('email') is-invalid @enderror"></div>
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-2">
                    <label class="form-label" for="rpPass">{{ __('New password') }} <span class="text-danger">*</span></label>
                    <div class="mk-field"><i class="bi bi-lock"></i><input id="rpPass" type="password" name="password" required minlength="10" maxlength="200" autofocus autocomplete="new-password" data-strength="#pwRules" class="form-control form-control-lg @error('password') is-invalid @enderror"><button type="button" class="mk-eye" data-pw-toggle aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button></div>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <ul class="mk-rules" id="pwRules" aria-live="polite">
                    <li data-rule="len"><i class="bi bi-circle"></i> {{ __('At least 10 characters') }}</li>
                    <li data-rule="letter"><i class="bi bi-circle"></i> {{ __('A letter') }}</li>
                    <li data-rule="digit"><i class="bi bi-circle"></i> {{ __('A number') }}</li>
                </ul>
                <div class="mb-3">
                    <label class="form-label" for="rpPass2">{{ __('Confirm password') }} <span class="text-danger">*</span></label>
                    <div class="mk-field"><i class="bi bi-shield-lock"></i><input id="rpPass2" type="password" name="password_confirmation" required maxlength="200" autocomplete="new-password" class="form-control form-control-lg"><button type="button" class="mk-eye" data-pw-toggle aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button></div>
                </div>
                <x-honeypot />
                <button class="btn btn-primary btn-lg w-100"><i class="bi bi-check2 me-1"></i> {{ __('Change password') }}</button>
            </form>
            <p class="mk-auth-switch"><a href="{{ route('password.request') }}">{{ __('Need a new link?') }}</a> · <a href="{{ route('login') }}">{{ __('Back to sign in') }}</a></p>
        </div>
    </div>
</div>
@endsection
