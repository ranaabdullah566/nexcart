@extends('store.account.layout', ['active' => 'orders', 'heading' => __('My orders')])
@section('panel')
@php $tone = fn ($o) => in_array($o->status, ['cancelled', 'refunded'], true) ? 'bad' : ($o->status === 'completed' ? 'ok' : 'now'); @endphp
<h1 class="ac-title">{{ __('My orders') }}</h1>
<div class="fx-tags ac-tabs" aria-label="{{ __('Filter orders') }}">
    @foreach (['all' => __('All'), 'open' => __('In progress'), 'done' => __('Completed'), 'cancelled' => __('Cancelled')] as $k => $label)
        <a href="{{ route('store.account.orders', $k === 'all' ? [] : ['show' => $k]) }}" @class(['on' => $filter === $k])>{{ $label }}</a>
    @endforeach
</div>
@forelse ($orders as $o)
    <article class="ac-order">
        <a href="{{ $o->trackUrl() }}" class="mk-order-card">
            <span class="mk-order-ic"><i class="bi bi-box-seam"></i></span>
            <div><b class="figure">{{ $o->number }}</b><div class="small text-muted">{{ local_time($o->placed_at, 'd M Y, h:i A') }} · {{ trans_choice(':n item|:n items', (int) $o->items_qty, ['n' => (int) $o->items_qty]) }}</div></div>
            <div class="text-end"><div class="figure fw-bold">{{ money($o->total) }}</div><span class="mk-status mk-status-{{ $tone($o) }}">{{ $o->statusLabel() }}</span></div>
            <i class="bi bi-chevron-right mk-go"></i>
        </a>
        <div class="ac-order-actions">
            <a class="btn btn-sm btn-outline-secondary" href="{{ $o->trackUrl() }}"><i class="bi bi-geo"></i> {{ __('Track') }}</a>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('store.invoice', [$o->number, $o->public_token]) }}" target="_blank" rel="noopener"><i class="bi bi-printer"></i> {{ __('Invoice') }}</a>
            <form method="POST" action="{{ route('store.order.reorder', [$o->number, $o->public_token]) }}" data-no-validate>@csrf<button class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat"></i> {{ __('Order again') }}</button></form>
        </div>
    </article>
@empty
    <div class="mk-empty"><span><i class="bi bi-receipt"></i></span><h2>{{ __('No orders here.') }}</h2><a href="{{ route('store.shop') }}" class="btn btn-primary">{{ __('Start shopping') }}</a></div>
@endforelse
<div class="mt-3">{{ $orders->links() }}</div>
@endsection
