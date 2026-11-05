@extends('layouts.admin')
@section('title', __('Security'))

@section('content')
<x-page-header :title="__('Security')" :eyebrow="__('Your account')" :sub="__('Protect your sign-in with a strong password and a second step.')" />

@if ($codes)
<div class="card mb-3 border-warning"><div class="card-body">
    <h2 class="h6"><i class="bi bi-key-fill me-1"></i> {{ __('Recovery codes') }}</h2>
    <p class="small text-muted">{{ __('Save these somewhere safe. Each code works once if you lose your phone. They will not be shown again.') }}</p>
    <div class="row g-2 font-monospace">@foreach ($codes as $c)<div class="col-6 col-md-3">{{ $c }}</div>@endforeach</div>
</div></div>
@endif

<div class="row g-3">
<div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
        <h2 class="h6 mb-3"><i class="bi bi-shield-lock-fill me-1"></i> {{ __('Two-factor authentication') }}
            @if ($user->two_factor_confirmed_at)<span class="badge text-bg-success ms-2">{{ __('On') }}</span>@else<span class="badge text-bg-secondary ms-2">{{ __('Off') }}</span>@endif</h2>
        @if ($user->two_factor_confirmed_at)
            <p class="small text-muted">{{ __('A code from your authenticator app is needed every time you sign in.') }}</p>
            <form method="POST" action="{{ route('admin.security.2fa.recovery') }}" class="mb-3" data-no-ajax>@csrf
                <label class="form-label small">{{ __('Current password') }}</label>
                <div class="input-group"><input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                <button class="btn btn-outline-secondary">{{ __('New recovery codes') }}</button></div>
            </form>
            <form method="POST" action="{{ route('admin.security.2fa.disable') }}" data-no-ajax data-confirm="{{ __('Turn off two-factor authentication?') }}" data-confirm-button="{{ __('Turn off') }}" data-confirm-note="{{ __('Your account will be less protected.') }}">@csrf @method('DELETE')
                <label class="form-label small">{{ __('Current password') }}</label>
                <div class="input-group"><input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                <button class="btn btn-outline-danger">{{ __('Turn off') }}</button></div>
                @error('current_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </form>
        @elseif ($setup)
            <p class="small text-muted">{{ __('Scan this with Google Authenticator, Authy or 1Password, then enter the 6-digit code.') }}</p>
            <div class="bg-white p-2 d-inline-block rounded mb-2">{!! $setup['qr'] !!}</div>
            <p class="small">{{ __('Or type this key:') }} <code class="user-select-all">{{ $setup['secret'] }}</code></p>
            <form method="POST" action="{{ route('admin.security.2fa.confirm') }}" data-no-ajax>@csrf
                <div class="input-group"><input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" class="form-control figure @error('code') is-invalid @enderror" placeholder="123456" required>
                <button class="btn btn-primary">{{ __('Confirm & turn on') }}</button></div>
                @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </form>
        @else
            <p class="small text-muted">{{ __('Add a second step to sign-in so a stolen password alone is not enough.') }}</p>
            <form method="POST" action="{{ route('admin.security.2fa.enable') }}" data-no-ajax>@csrf<button class="btn btn-primary"><i class="bi bi-shield-plus me-1"></i> {{ __('Set up') }}</button></form>
        @endif
    </div></div>
</div>

<div class="col-lg-6">
    <div class="card h-100"><div class="card-body">
        <h2 class="h6 mb-3"><i class="bi bi-key me-1"></i> {{ __('Change password') }}</h2>
        <form method="POST" action="{{ route('admin.security.password') }}" data-no-ajax>@csrf @method('PUT')
            <div class="mb-2"><label class="form-label small">{{ __('Current password') }}</label><input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required autocomplete="current-password">@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-2"><label class="form-label small">{{ __('New password') }}</label><input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">{{ __('At least 10 characters, with letters and numbers.') }}</div></div>
            <div class="mb-3"><label class="form-label small">{{ __('Confirm new password') }}</label><input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password"></div>
            <button class="btn btn-primary">{{ __('Change password') }}</button>
        </form>
    </div></div>
</div>

@if ($canPolicy)
<div class="col-12">
    <div class="card"><div class="card-body">
        <h2 class="h6 mb-3"><i class="bi bi-people-fill me-1"></i> {{ __('Team policy') }}</h2>
        <form method="POST" action="{{ route('admin.security.policy') }}" data-no-ajax class="row g-3 align-items-end">@csrf
            <div class="col-md-4"><label class="form-label small">{{ __('Sign out after inactivity (minutes, 0 = never)') }}</label><input type="number" min="0" max="1440" name="idle_minutes" value="{{ setting('security.idle_minutes', 120) }}" class="form-control figure"></div>
            <div class="col-md-5"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="require_2fa" id="req2fa" value="1" @checked(setting('security.require_2fa'))><label class="form-check-label" for="req2fa">{{ __('Require two-factor authentication for all staff') }}</label></div></div>
            <div class="col-md-3"><button class="btn btn-primary w-100">{{ __('Save') }}</button></div>
        </form>
    </div></div>
</div>
@endif

@if ($canPolicy)
<div class="col-12">
    <div class="card"><div class="card-body">
        <h2 class="h6 mb-1"><i class="bi bi-plug-fill me-1"></i> {{ __('Bot protection & Google sign-in') }}</h2>
        <p class="small text-muted mb-3">{{ __('Keys come from Google (reCAPTCHA v2 “I’m not a robot” checkbox, and a Google Cloud OAuth client). Leave a secret blank to keep the saved one.') }}</p>
        <form method="POST" action="{{ route('admin.security.integrations') }}" data-no-ajax class="row g-3">@csrf
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="recaptcha_enabled" id="rc" value="1" @checked(setting('integrations.recaptcha_enabled'))><label class="form-check-label" for="rc">{{ __('Google reCAPTCHA on sign-in, sign-up, checkout and order tracking') }}</label></div></div>
            <div class="col-md-6"><label class="form-label small">{{ __('reCAPTCHA site key') }}</label><input name="recaptcha_site" value="{{ setting('integrations.recaptcha_site') }}" class="form-control font-monospace" autocomplete="off"></div>
            <div class="col-md-6"><label class="form-label small">{{ __('reCAPTCHA secret key') }} @if (\App\Support\Integrations::secret('recaptcha_secret'))<span class="badge text-bg-success">{{ __('saved') }}</span>@endif</label><input type="password" name="recaptcha_secret" class="form-control font-monospace" autocomplete="new-password" placeholder="••••••••"></div>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="google_enabled" id="gl" value="1" @checked(setting('integrations.google_enabled'))><label class="form-check-label" for="gl">{{ __('Let customers sign in with Google') }}</label></div></div>
            <div class="col-md-6"><label class="form-label small">{{ __('Google client ID') }}</label><input name="google_client_id" value="{{ setting('integrations.google_client_id') }}" class="form-control font-monospace" autocomplete="off"></div>
            <div class="col-md-6"><label class="form-label small">{{ __('Google client secret') }} @if (\App\Support\Integrations::secret('google_client_secret'))<span class="badge text-bg-success">{{ __('saved') }}</span>@endif</label><input type="password" name="google_client_secret" class="form-control font-monospace" autocomplete="new-password" placeholder="••••••••"></div>
            <div class="col-12 small text-muted">{{ __('Authorised redirect URI to add in Google Cloud:') }} <code class="user-select-all">{{ url('/auth/google/callback') }}</code></div>
            <div class="col-12"><button class="btn btn-primary">{{ __('Save') }}</button></div>
        </form>
    </div></div>
</div>
@endif
</div>
@endsection
