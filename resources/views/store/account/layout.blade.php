@extends('layouts.store')
@php $page = 'account'; @endphp
@section('title', $heading ?? __('My account'))

@section('content')
@php
    $nav = [
        'dashboard' => ['store.account', 'bi-grid-1x2', __('Dashboard')],
        'orders' => ['store.account.orders', 'bi-bag-check', __('My orders')],
        'addresses' => ['store.account.addresses', 'bi-geo-alt', __('Addresses')],
        'prescriptions' => ['store.account.prescriptions', 'bi-file-medical', __('Prescriptions')],
        'saved' => ['store.wishlist', 'bi-heart', __('Saved medicines')],
        'reviews' => ['store.account.reviews', 'bi-star', __('My reviews')],
        'profile' => ['store.account.profile', 'bi-person-gear', __('Profile & password')],
    ];
@endphp
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><a href="{{ route('store.account') }}">{{ __('My account') }}</a>@if (($active ?? 'dashboard') !== 'dashboard')<span>›</span><span>{{ $nav[$active][2] }}</span>@endif</nav>

    @if ($errors->any())<div class="sf-alert mb-3" role="alert"><i class="bi bi-exclamation-triangle-fill fs-5"></i><div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>@endif

    <div class="ac-shell">
        <aside class="ac-side" aria-label="{{ __('Account menu') }}">
            <div class="ac-me"><span class="mk-avatar lg">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span><div><b>{{ $user->name }}</b><small>{{ $user->email }}</small></div></div>
            <nav>
                @foreach ($nav as $key => [$route, $icon, $label])
                    <a href="{{ route($route) }}" @class(['on' => ($active ?? 'dashboard') === $key]) @if (($active ?? 'dashboard') === $key) aria-current="page" @endif><i class="bi {{ $icon }}"></i><span>{{ $label }}</span></a>
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}" data-no-validate>@csrf<button class="ac-out"><i class="bi bi-box-arrow-right"></i> {{ __('Sign out') }}</button></form>
        </aside>
        <main class="ac-main" id="acMain">
            @yield('panel')
        </main>
    </div>
</div>
@endsection
