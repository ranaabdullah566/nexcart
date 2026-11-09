@extends('store.account.layout', ['active' => 'dashboard', 'heading' => __('My account')])
@section('panel')
@php
    $live = $orders->reject(fn ($o) => in_array($o->status, ['cancelled', 'refunded'], true));
    $tone = fn ($o) => in_array($o->status, ['cancelled', 'refunded'], true) ? 'bad' : ($o->status === 'completed' ? 'ok' : 'now');
    $open = $orders->filter(fn ($o) => $o->isOpen());
@endphp
<h1 class="ac-title">{{ __('Hello, :name', ['name' => explode(' ', $user->name)[0]]) }} 👋</h1>

<div class="mk-stats">
    <a href="{{ route('store.account.orders') }}"><i class="bi bi-receipt"></i><b class="figure">{{ $orders->count() }}</b><span>{{ __('Orders') }}</span></a>
    <a href="{{ route('store.account.orders', ['show' => 'open']) }}"><i class="bi bi-hourglass-split"></i><b class="figure">{{ $open->count() }}</b><span>{{ __('In progress') }}</span></a>
    <a href="{{ route('store.account.orders') }}"><i class="bi bi-cash-coin"></i><b class="figure">{{ money($live->sum('total')) }}</b><span>{{ __('Total spent') }}</span></a>
    <a href="{{ route('store.account.addresses') }}"><i class="bi bi-geo-alt"></i><b class="figure">{{ $addressCount }}</b><span>{{ __('Saved addresses') }}</span></a>
</div>

@if ($open->isNotEmpty())
<section class="mk-box">
    <h2 class="mk-box-title"><i class="bi bi-truck"></i> {{ __('On its way') }}</h2>
    @foreach ($open->take(2) as $o)
        <a href="{{ $o->trackUrl() }}" class="mk-order-card">
            <span class="mk-order-ic"><i class="bi bi-box-seam"></i></span>
            <div><b class="figure">{{ $o->number }}</b><div class="small text-muted">{{ local_time($o->placed_at, 'd M Y') }}</div></div>
            <div class="text-end"><div class="figure fw-bold">{{ money($o->total) }}</div><span class="mk-status mk-status-now">{{ $o->statusLabel() }}</span></div>
            <i class="bi bi-chevron-right mk-go"></i>
        </a>
    @endforeach
</section>
@endif

<section class="mk-box">
    <h2 class="mk-box-title"><i class="bi bi-bag-check"></i> {{ __('Recent orders') }}<a class="ac-more" href="{{ route('store.account.orders') }}">{{ __('View all') }} →</a></h2>
    @forelse ($recent as $o)
        <a href="{{ $o->trackUrl() }}" class="mk-order-card">
            <span class="mk-order-ic"><i class="bi bi-box-seam"></i></span>
            <div><b class="figure">{{ $o->number }}</b><div class="small text-muted">{{ local_time($o->placed_at, 'd M Y') }} · {{ trans_choice(':n item|:n items', (int) $o->items_qty, ['n' => (int) $o->items_qty]) }}</div></div>
            <div class="text-end"><div class="figure fw-bold">{{ money($o->total) }}</div><span class="mk-status mk-status-{{ $tone($o) }}">{{ $o->statusLabel() }}</span></div>
            <i class="bi bi-chevron-right mk-go"></i>
        </a>
    @empty
        <div class="mk-empty"><span><i class="bi bi-receipt"></i></span><h2>{{ __('No orders yet.') }}</h2><a href="{{ route('store.shop') }}" class="btn btn-primary">{{ __('Start shopping') }}</a></div>
    @endforelse
</section>

<div class="ac-quick">
    <a href="{{ route('store.prescription.upload') }}"><i class="bi bi-camera"></i><b>{{ __('Upload prescription') }}</b><small>{{ __('Send a photo, we call you back.') }}</small></a>
    <a href="{{ route('store.wishlist') }}"><i class="bi bi-heart"></i><b>{{ __('Saved medicines') }}</b><small>{{ __('Your shortlist on this device.') }}</small></a>
    <a href="{{ route('store.track') }}"><i class="bi bi-geo"></i><b>{{ __('Track an order') }}</b><small>{{ __('Use an order number.') }}</small></a>
</div>

@if ($rx->isNotEmpty())
<section class="mk-box">
    <h2 class="mk-box-title"><i class="bi bi-file-medical"></i> {{ __('Prescriptions') }}<a class="ac-more" href="{{ route('store.account.prescriptions') }}">{{ __('View all') }} →</a></h2>
    @foreach ($rx as $r)<div class="ac-row"><div><b>{{ local_time($r->created_at, 'd M Y') }}</b><small>{{ trans_choice(':n file|:n files', $r->media->count(), ['n' => $r->media->count()]) }}</small></div><span class="mk-status mk-status-{{ $r->status === 'closed' ? 'ok' : 'now' }}">{{ __(ucfirst($r->status)) }}</span></div>@endforeach
</section>
@endif
@endsection
