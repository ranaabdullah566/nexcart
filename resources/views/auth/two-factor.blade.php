@extends('layouts.guest')
@section('title', __('Two-factor check'))
@section('card_class', 'narrow')

@section('content')
<div class="text-center mb-4">
    <span class="nx-brand-logo mx-auto mb-3" style="width:56px;height:56px"><i class="bi bi-shield-lock-fill fs-2"></i></span>
    <h1 class="nx-title">{{ __('Two-factor check') }}</h1>
    <p class="nx-sub">{{ __('Enter the 6-digit code from your authenticator app, or a recovery code.') }}</p>
</div>
<div class="card nx-form-card"><div class="card-body p-4">
    <form method="POST" action="{{ url('/login/two-factor') }}">
        @csrf
        <input name="code" autofocus autocomplete="one-time-code" required maxlength="20" class="form-control text-center figure @error('code') is-invalid @enderror" placeholder="123456">
        @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <button class="btn btn-primary w-100 mt-3"><i class="bi bi-check2 me-1"></i> {{ __('Verify') }}</button>
    </form>
    <div class="text-center mt-3 small"><a href="{{ route('login') }}">{{ __('Back to sign in') }}</a></div>
</div></div>
@endsection
