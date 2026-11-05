@extends('layouts.store')
@section('title', __('Order :number', ['number' => $order->number]))

@section('content')
@php
    $page = 'order';
    $steps = $order->timelineSteps();
    $cancelled = in_array($order->status, ['cancelled', 'refunded'], true);
    $idx = array_search($order->status, $steps, true);
@endphp
<div class="sf-container py-4" style="max-width:980px">
    @if (session('placed'))
        <div class="sf-banner mb-4" style="min-height:0"><div>
            <span class="sf-eyebrow">{{ __('Order placed') }}</span>
            <h1 class="h2 mt-2">{{ __('Thank you, :name!', ['name' => explode(' ', $order->name)[0]]) }}</h1>
            <p class="text-muted mb-0">{{ __('Your order :number is in. We sent a confirmation to :email.', ['number' => $order->number, 'email' => $order->email]) }}</p>
        </div></div>
    @endif
    @if (session('success'))<div class="sf-alert mb-3" style="border-color:var(--ok)"><i class="bi bi-check-circle-fill text-success fs-5"></i>{{ session('success') }}</div>@endif
    @if (session('error'))<div class="sf-alert mb-3" role="alert"><i class="bi bi-exclamation-triangle-fill fs-5"></i>{{ session('error') }}</div>@endif

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div><div class="sf-eyebrow">{{ __('Order') }}</div><h1 class="h2 mb-0 figure">{{ $order->number }}</h1><div class="text-muted small">{{ $order->placed_at?->format('d M Y, H:i') }}</div></div>
        <div class="d-flex gap-2 flex-wrap"><a href="{{ route('store.invoice', [$order->number, $order->public_token]) }}" target="_blank" class="sf-btn-ghost"><i class="bi bi-printer"></i> {{ __('Invoice') }}</a></div>
    </div>

    <div class="sf-summary mb-4">
        @if ($cancelled)
            <div class="d-flex align-items-center gap-3"><i class="bi bi-x-octagon-fill fs-2 text-danger"></i><div><b>{{ $order->statusLabel() }}</b><div class="text-muted small">{{ __('This order is no longer active.') }}</div></div></div>
        @else
            <ol class="ck-track">
                @foreach ($steps as $i => $s)
                    <li class="{{ $idx !== false && $i <= $idx ? 'done' : '' }} {{ $idx === $i ? 'now' : '' }}"><span><i class="bi bi-{{ $idx !== false && $i < $idx ? 'check-lg' : 'circle-fill' }}"></i></span><b>{{ __(str_replace('_', ' ', ucfirst($s))) }}</b></li>
                @endforeach
            </ol>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="sf-summary">
                <h2 class="h5 mb-3">{{ __('Items') }}</h2>
                @foreach ($order->items as $i)
                    <div class="d-flex justify-content-between gap-3 py-2 border-bottom" style="border-color:var(--border) !important">
                        <div><b>{{ $i->qty }} × {{ $i->name }}</b>@if ($i->variant_name) <span class="text-muted">· {{ $i->variant_name }}</span>@endif
                            @if ($i->options)<div class="small text-muted">{{ collect($i->options)->pluck('name')->implode(', ') }}</div>@endif</div>
                        <span class="figure">{{ money($i->total) }}</span>
                    </div>
                @endforeach
                <div class="mt-3">
                    <div class="ck-row"><span>{{ __('Subtotal') }}</span><b class="figure">{{ money($order->subtotal) }}</b></div>
                    @if ($order->discount > 0)<div class="ck-row"><span>{{ __('Discount') }} @if ($order->coupon_code)<small>({{ $order->coupon_code }})</small>@endif</span><b class="figure text-success">−{{ money($order->discount) }}</b></div>@endif
                    <div class="ck-row"><span>{{ $order->shipping_method }}</span><b class="figure">{{ $order->shipping_cost > 0 ? money($order->shipping_cost) : __('Free') }}</b></div>
                    @if ($order->tax > 0)<div class="ck-row"><span>{{ __('Tax') }} @if ($order->tax_inclusive)<small>({{ __('included') }})</small>@endif</span><b class="figure">{{ money($order->tax) }}</b></div>@endif
                    <div class="ck-row ck-total"><span>{{ __('Total') }}</span><b class="figure">{{ money($order->total) }}</b></div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="sf-summary mb-3">
                <h2 class="h6 text-uppercase text-muted">{{ $order->is_pickup ? __('Pickup') : __('Delivery') }}</h2>
                <div class="fw-semibold">{{ $order->name }}</div>
                <div class="text-muted">{{ $order->phone }}<br>{{ $order->email }}</div>
                @unless ($order->is_pickup)<div class="mt-2">{{ $order->address_line }}@if ($order->area), {{ $order->area }}@endif<br>{{ $order->city }} {{ $order->postal_code }}</div>@endunless
                <div class="mt-2 small text-muted">{{ $order->shipping_method }}</div>
                @if ($order->notes)<div class="mt-2 small"><b>{{ __('Notes') }}:</b> {{ $order->notes }}</div>@endif
            </div>
            <div class="sf-summary">
                <h2 class="h6 text-uppercase text-muted">{{ __('Payment') }}</h2>
                <div class="d-flex justify-content-between align-items-center"><b>{{ ['cod' => __('Cash on delivery'), 'bank' => __('Bank transfer'), 'stripe' => __('Card (Stripe)')][$order->payment_method] ?? $order->payment_method }}</b>
                    <span class="badge rounded-pill text-bg-{{ ['paid' => 'success', 'unpaid' => 'warning', 'failed' => 'danger', 'refunded' => 'secondary'][$order->payment_status] }}">{{ __(ucfirst($order->payment_status)) }}</span></div>
                @if ($bank && $order->payment_status === 'unpaid')<div class="mt-3 small" style="white-space:pre-line;border-top:1px dashed var(--border);padding-top:.75rem">{{ $bank }}<div class="mt-2 text-muted">{{ __('Use :number as the payment reference.', ['number' => $order->number]) }}</div></div>@endif
                @if ($order->payment_method === 'stripe' && $order->payment_status === 'unpaid' && $order->isOpen())
                    <form method="POST" action="{{ route('store.order.pay', [$order->number, $order->public_token]) }}" class="mt-3">@csrf<button class="btn btn-primary w-100"><i class="bi bi-credit-card me-1"></i> {{ __('Pay now') }}</button></form>
                @endif
                @if ($order->requires_prescription)<div class="mt-3 small"><i class="bi bi-prescription2 me-1"></i>{{ __('Prescription received — a pharmacist will verify it before dispatch.') }}</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('head')
<style>
    .ck-track { list-style: none; display: flex; justify-content: space-between; padding: 0; margin: 0; position: relative; gap: .5rem; }
    .ck-track::before { content: ''; position: absolute; top: 15px; inset-inline: 8%; height: 3px; background: var(--border-strong); border-radius: 3px; }
    .ck-track li { position: relative; flex: 1; text-align: center; display: grid; justify-items: center; gap: .5rem; font-size: .8rem; color: var(--text-3); }
    .ck-track li span { width: 32px; height: 32px; border-radius: 50%; background: var(--surface-solid); border: 2px solid var(--border-strong); display: grid; place-items: center; z-index: 1; font-size: .7rem; }
    .ck-track li.done { color: var(--text); } .ck-track li.done span { background: var(--grad); border-color: transparent; color: #fff; }
    .ck-track li.now span { box-shadow: var(--glow); }
    .ck-row { display: flex; justify-content: space-between; padding: .3rem 0; color: var(--text-2); } .ck-row b { color: var(--text); font-weight: 600; }
    .ck-total { border-top: 1px solid var(--border); margin-top: .5rem; padding-top: .8rem; font-size: 1.15rem; color: var(--text); }
</style>
@endpush
