@extends('layouts.admin')
@section('title', __('Deliveries'))

@section('content')
<x-page-header :title="$mine ? __('My deliveries') : __('Deliveries')" :eyebrow="__('Riders')" :sub="__('Tap “Picked up” when you leave with the order, then “Delivered” at the door.')" />

<div class="d-grid gap-3" style="max-width:720px">
@forelse ($orders as $o)
    @php($due = $o->payment_status === 'unpaid' && $o->payment_method === 'cod')
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div><b class="figure fs-5">{{ $o->number }}</b>
                <div class="small text-muted">{{ $o->statusLabel() }} @unless ($mine)· {{ $o->rider?->name }}@endunless</div></div>
            <div class="text-end">@if ($o->eta_at)<div class="figure fw-bold">{{ $o->eta_at->timezone(setting('store.timezone'))->format('H:i') }}</div><div class="small text-muted">{{ __('ETA') }}</div>@endif</div>
        </div>
        <hr>
        <div class="fw-semibold">{{ $o->name }}</div>
        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $o->phone) }}" class="d-inline-flex align-items-center gap-2 mb-2" dir="ltr"><i class="bi bi-telephone-fill"></i>{{ $o->phone }}</a>
        <div>{{ $o->address_line }}@if ($o->area), {{ $o->area }}@endif, {{ $o->city }}</div>
        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(trim($o->address_line.' '.$o->area.' '.$o->city)) }}" target="_blank" rel="noopener" class="small" data-no-modal><i class="bi bi-geo-alt-fill me-1"></i>{{ __('Open in Maps') }}</a>
        @if ($o->notes)<div class="mt-2 small p-2 rounded-3" style="background:var(--surface-2)"><i class="bi bi-chat-left-text me-1"></i>{{ $o->notes }}</div>@endif
        <div class="mt-2 small text-muted">{{ $o->items->sum('qty') }} {{ __('items') }} · {{ $o->items->pluck('name')->take(3)->implode(', ') }}</div>
        @if ($due)<div class="alert alert-warning mt-3 mb-0 d-flex justify-content-between align-items-center py-2"><span><i class="bi bi-cash-coin me-1"></i>{{ __('Collect cash') }}</span><b class="figure fs-5">{{ money($o->total) }}</b></div>
        @else<div class="mt-2 small text-success"><i class="bi bi-check-circle-fill me-1"></i>{{ __('Already paid') }}</div>@endif

        @if (in_array($o->status, ['ready', 'out_for_delivery'], true))
            <form method="POST" action="{{ route('admin.deliveries.advance', $o) }}" data-no-ajax class="mt-3">@csrf
                <button class="btn btn-primary btn-lg w-100">@if ($o->status === 'ready')<i class="bi bi-bag-check me-1"></i> {{ __('Picked up') }}@else<i class="bi bi-check2-circle me-1"></i> {{ __('Delivered') }}@endif</button></form>
        @else
            <div class="mt-3 text-center small text-muted"><i class="bi bi-hourglass-split me-1"></i>{{ __('Being prepared — you will be able to pick it up soon.') }}</div>
        @endif
    </div></div>
@empty
    <div class="card"><div class="card-body text-center text-muted py-5"><i class="bi bi-bicycle fs-1 d-block mb-2"></i>{{ __('No deliveries assigned to you right now.') }}</div></div>
@endforelse
</div>
@endsection
