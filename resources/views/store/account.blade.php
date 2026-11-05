@extends('layouts.store')
@php($page = 'account')
@section('title', __('My account'))

@section('content')
<div class="sf-container py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4"><h1 class="h2 mb-0">{{ __('My account') }}</h1>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="sf-btn-ghost"><i class="bi bi-box-arrow-right"></i> {{ __('Sign out') }}</button></form></div>
    @if ($errors->any())<div class="sf-alert mb-3" role="alert"><i class="bi bi-exclamation-triangle-fill fs-5"></i><div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>@endif

    <div class="row g-4">
        <div class="col-lg-7">
            <h2 class="h5 mb-3">{{ __('My orders') }}</h2>
            @forelse ($orders as $o)
                <a href="{{ $o->trackUrl() }}" class="sf-summary d-flex justify-content-between align-items-center gap-3 mb-2 text-decoration-none" style="color:var(--text)">
                    <div><b class="figure">{{ $o->number }}</b><div class="small text-muted">{{ local_time($o->placed_at, 'd M Y') }} · {{ trans_choice(':n item|:n items', $o->items()->sum('qty'), ['n' => $o->items()->sum('qty')]) }}</div></div>
                    <div class="text-end"><div class="figure fw-bold">{{ money($o->total) }}</div><span class="badge rounded-pill text-bg-secondary">{{ $o->statusLabel() }}</span></div>
                </a>
            @empty
                <div class="sf-empty"><i class="bi bi-receipt"></i>{{ __('No orders yet.') }}<div><a href="{{ route('store.shop') }}" class="btn btn-primary mt-3">{{ __('Start shopping') }}</a></div></div>
            @endforelse
        </div>
        <div class="col-lg-5">
            <h2 class="h5 mb-3">{{ __('Profile & address') }}</h2>
            @php($a = $user->default_address ?? [])
            <form method="POST" action="{{ route('store.account.update') }}" class="sf-summary">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">{{ __('Full name') }}</label><input name="name" value="{{ old('name', $user->name) }}" required class="form-control"></div>
                    <div class="col-md-7"><label class="form-label">{{ __('Email') }}</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control"></div>
                    <div class="col-md-5"><label class="form-label">{{ __('Phone') }}</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="form-control"></div>
                    <div class="col-12"><label class="form-label">{{ __('Address') }}</label><input name="address_line" value="{{ old('address_line', $a['address_line'] ?? '') }}" class="form-control"></div>
                    <div class="col-md-5"><label class="form-label">{{ __('City') }}</label><input name="city" value="{{ old('city', $a['city'] ?? '') }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">{{ __('Area') }}</label><input name="area" value="{{ old('area', $a['area'] ?? '') }}" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Postal code') }}</label><input name="postal_code" value="{{ old('postal_code', $a['postal_code'] ?? '') }}" class="form-control"></div>
                    <div class="col-md-12"><label class="form-label">{{ __('Current password') }}</label><input type="password" name="current_password" autocomplete="current-password" class="form-control @error('current_password') is-invalid @enderror"><div class="form-text">{{ __('Needed only to change your email or password.') }}</div>@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">{{ __('New password') }}</label><input type="password" name="password" autocomplete="new-password" class="form-control"><div class="form-text">{{ __('Leave blank to keep the current password.') }}</div></div>
                    <div class="col-md-6"><label class="form-label">{{ __('Confirm password') }}</label><input type="password" name="password_confirmation" autocomplete="new-password" class="form-control"></div>
                </div>
                <button class="btn btn-primary w-100 mt-3">{{ __('Save') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
