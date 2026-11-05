@extends('layouts.store')
@php $page = 'auth'; @endphp
@section('title', __('Two-factor check'))

@section('content')
<div class="sf-container py-3">
    <div class="mk-auth mk-auth-solo">
        <div class="mk-box mk-auth-card text-center">
            <span class="mk-success-ic mx-auto"><i class="bi bi-shield-lock-fill"></i></span>
            <h1>{{ __('Two-factor check') }}</h1>
            <p class="mk-sub mb-3">{{ __('Enter the 6-digit code from your authenticator app, or a recovery code.') }}</p>
            <form method="POST" action="{{ url('/login/two-factor') }}">
                @csrf
                <input name="code" autofocus autocomplete="one-time-code" required maxlength="20" inputmode="text" aria-label="{{ __('Code') }}" class="form-control form-control-lg text-center figure mk-code @error('code') is-invalid @enderror" placeholder="123456">
                @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <button class="btn btn-primary btn-lg w-100 mt-3"><i class="bi bi-check2 me-1"></i> {{ __('Verify') }}</button>
            </form>
            <p class="mk-auth-switch"><a href="{{ route('login') }}">{{ __('Back to sign in') }}</a></p>
        </div>
    </div>
</div>
@endsection
