@extends('layouts.store')
@php $page = 'account'; @endphp
@section('title', __('My account'))

@section('content')
@php
    $live = $orders->reject(fn ($o) => in_array($o->status, ['cancelled', 'refunded'], true));
    $points = app(\App\Services\Loyalty::class)->enabled() ? app(\App\Services\Loyalty::class)->balance($user) : null;
    $a = $user->default_address ?? [];
    $tone = fn ($o) => in_array($o->status, ['cancelled', 'refunded'], true) ? 'bad' : ($o->status === 'completed' ? 'ok' : 'now');
@endphp
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><span>{{ __('My account') }}</span></nav>

    <header class="mk-acct-head">
        <span class="mk-avatar lg">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
        <div><h1>{{ __('Hello, :name', ['name' => explode(' ', $user->name)[0]]) }}</h1><div class="text-muted">{{ $user->email }}</div></div>
        <form method="POST" action="{{ route('logout') }}" class="ms-auto" data-no-validate>@csrf<button class="sf-btn-ghost"><i class="bi bi-box-arrow-right"></i> {{ __('Sign out') }}</button></form>
    </header>

    <div class="mk-stats">
        <div><i class="bi bi-receipt"></i><b class="figure">{{ $orders->count() }}</b><span>{{ __('Orders') }}</span></div>
        <div><i class="bi bi-cash-coin"></i><b class="figure">{{ money($live->sum('total')) }}</b><span>{{ __('Total spent') }}</span></div>
        <div><i class="bi bi-hourglass-split"></i><b class="figure">{{ $orders->filter(fn ($o) => $o->isOpen())->count() }}</b><span>{{ __('In progress') }}</span></div>
        @if ($points !== null)<div><i class="bi bi-stars"></i><b class="figure">{{ number_format($points) }}</b><span>{{ __('Points') }}</span></div>@endif
    </div>

    @if ($errors->any())<div class="sf-alert mb-3" role="alert"><i class="bi bi-exclamation-triangle-fill fs-5"></i><div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>@endif

    <div class="mk-acct">
        <section>
            <h2 class="mk-box-title"><i class="bi bi-bag-check"></i> {{ __('My orders') }}</h2>
            @forelse ($orders as $o)
                <a href="{{ $o->trackUrl() }}" class="mk-order-card">
                    <span class="mk-order-ic"><i class="bi bi-box-seam"></i></span>
                    <div><b class="figure">{{ $o->number }}</b><div class="small text-muted">{{ local_time($o->placed_at, 'd M Y') }} · {{ trans_choice(':n item|:n items', $o->items()->sum('qty'), ['n' => $o->items()->sum('qty')]) }}</div></div>
                    <div class="text-end"><div class="figure fw-bold">{{ money($o->total) }}</div><span class="mk-status mk-status-{{ $tone($o) }}">{{ $o->statusLabel() }}</span></div>
                    <i class="bi bi-chevron-right mk-go"></i>
                </a>
            @empty
                <div class="mk-empty"><span><i class="bi bi-receipt"></i></span><h2>{{ __('No orders yet.') }}</h2><a href="{{ route('store.shop') }}" class="btn btn-primary">{{ __('Start shopping') }}</a></div>
            @endforelse
        </section>

        <section>
            <h2 class="mk-box-title"><i class="bi bi-person-gear"></i> {{ __('Profile & address') }}</h2>
            <form method="POST" action="{{ route('store.account.update') }}" class="mk-box">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">{{ __('Full name') }}</label><input name="name" value="{{ old('name', $user->name) }}" required maxlength="120" class="form-control"></div>
                    <div class="col-md-7"><label class="form-label">{{ __('Email') }}</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="190" class="form-control"></div>
                    <div class="col-md-5"><label class="form-label">{{ __('Phone') }}</label><input name="phone" value="{{ old('phone', $user->phone) }}" maxlength="40" class="form-control"></div>
                    <div class="col-12"><label class="form-label">{{ __('Address') }}</label><input name="address_line" value="{{ old('address_line', $a['address_line'] ?? '') }}" maxlength="255" class="form-control"></div>
                    <div class="col-md-5"><label class="form-label">{{ __('City') }}</label><input name="city" value="{{ old('city', $a['city'] ?? '') }}" maxlength="120" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">{{ __('Area') }}</label><input name="area" value="{{ old('area', $a['area'] ?? '') }}" maxlength="120" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Postal code') }}</label><input name="postal_code" value="{{ old('postal_code', $a['postal_code'] ?? '') }}" maxlength="20" class="form-control"></div>
                </div>
                <details class="mk-pw" @if ($errors->has('current_password') || $errors->has('password')) open @endif>
                    <summary><i class="bi bi-key"></i> {{ __('Change email or password') }}<i class="bi bi-chevron-down"></i></summary>
                    <div class="row g-3 mt-0">
                        <div class="col-12"><label class="form-label">{{ __('Current password') }}</label><input type="password" name="current_password" autocomplete="current-password" class="form-control @error('current_password') is-invalid @enderror"><div class="form-text">{{ __('Needed only to change your email or password.') }}</div>@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label">{{ __('New password') }}</label><input type="password" name="password" minlength="10" autocomplete="new-password" class="form-control"><div class="form-text">{{ __('Leave blank to keep the current password.') }}</div></div>
                        <div class="col-md-6"><label class="form-label">{{ __('Confirm password') }}</label><input type="password" name="password_confirmation" autocomplete="new-password" class="form-control"></div>
                    </div>
                </details>
                <button class="btn btn-primary btn-lg w-100 mt-3"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
            </form>
        </section>
    </div>
</div>
@endsection
