@extends('layouts.admin')
@section('title', __('Order :number', ['number' => $order->number]))

@section('content')
@php($next = $order->nextStatuses())
<x-page-header :back="route('admin.orders.index')" :title="$order->number" :eyebrow="__('Order')" :sub="local_time($order->placed_at, 'd M Y, H:i').' · '.($order->is_pickup ? __('Pickup') : __('Delivery'))">
    <x-slot:actions>
        <a href="{{ route('store.invoice', [$order->number, $order->public_token]) }}" target="_blank" class="btn btn-outline-secondary" data-no-modal><i class="bi bi-printer me-1"></i> {{ __('Invoice') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="d-flex gap-2 flex-wrap mb-3">
    <span class="badge rounded-pill text-bg-secondary fs-6 px-3 py-2">{{ $order->statusLabel() }}</span>
    <span class="badge rounded-pill text-bg-{{ ['paid' => 'success', 'unpaid' => 'warning', 'failed' => 'danger', 'refunded' => 'secondary'][$order->payment_status] }} fs-6 px-3 py-2">{{ __(ucfirst($order->payment_status)) }} · {{ ['cod' => __('Cash on delivery'), 'bank' => __('Bank transfer'), 'stripe' => __('Card (Stripe)')][$order->payment_method] ?? $order->payment_method }}</span>
</div>

@if ($order->slot_label)<div class="sf-alert mb-3 d-flex gap-2 align-items-center"><i class="bi bi-calendar-check fs-5"></i><b>{{ $order->is_pickup ? __('Pickup time') : __('Delivery time') }}: {{ $order->slot_date->format('D, d M') }} · {{ $order->slot_label }}</b></div>@endif
@if ($order->eta_at && $order->isOpen())<div class="mb-3 small text-muted"><i class="bi bi-stopwatch me-1"></i>{{ __('Estimated arrival') }}: <b class="figure">{{ $order->eta_at->timezone(setting('store.timezone'))->format('h:i A') }}</b></div>@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3"><div class="card-body">
            <h6 class="text-uppercase text-muted small">{{ __('Items') }}</h6>
            @foreach ($order->items as $i)
                <div class="d-flex justify-content-between gap-3 py-2 border-bottom" style="border-color:var(--border) !important">
                    <div><b>{{ $i->qty }} × {{ $i->name }}</b>@if ($i->variant_name) <span class="text-muted">· {{ $i->variant_name }}</span>@endif
                        @if ($i->options)<div class="small text-muted">{{ collect($i->options)->map(fn ($o) => $o['name'].($o['price'] ? ' (+'.money($o['price']).')' : ''))->implode(', ') }}</div>@endif
                        @if ($i->sku)<div class="small text-muted figure">{{ $i->sku }}</div>@endif</div>
                    <span class="figure">{{ money($i->total) }}</span>
                </div>
            @endforeach
            <div class="mt-3 small">
                <div class="d-flex justify-content-between"><span class="text-muted">{{ __('Subtotal') }}</span><span class="figure">{{ money($order->subtotal) }}</span></div>
                @if ($order->discount > 0)<div class="d-flex justify-content-between"><span class="text-muted">{{ __('Discount') }} {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</span><span class="figure text-success">−{{ money($order->discount) }}</span></div>@endif
                @if ($order->points_discount > 0)<div class="d-flex justify-content-between"><span class="text-muted">{{ __('Points used') }} ({{ $order->points_redeemed }})</span><span class="figure text-success">−{{ money($order->points_discount) }}</span></div>@endif
                <div class="d-flex justify-content-between"><span class="text-muted">{{ $order->shipping_method }}</span><span class="figure">{{ money($order->shipping_cost) }}</span></div>
                @if ($order->tax > 0)<div class="d-flex justify-content-between"><span class="text-muted">{{ __('Tax') }} @if ($order->tax_inclusive)({{ __('included') }})@endif</span><span class="figure">{{ money($order->tax) }}</span></div>@endif
                <div class="d-flex justify-content-between fs-5 mt-2"><b>{{ __('Total') }}</b><b class="figure">{{ money($order->total) }}</b></div>
            </div>
        </div></div>

        <div class="card"><div class="card-body">
            <h6 class="text-uppercase text-muted small">{{ __('History') }}</h6>
            @foreach ($order->events->reverse() as $e)
                <div class="d-flex gap-2 py-1 small"><span class="text-muted figure text-nowrap">{{ local_time($e->created_at, 'd M H:i') }}</span>
                    <span><b>{{ $e->type === 'placed' ? __('Order placed') : ($e->type === 'payment' ? __('Payment').': '.__(ucfirst((string) $e->status)) : __(str_replace('_', ' ', ucfirst((string) $e->status)))) }}</b>
                        @if ($e->note) — {{ $e->note }}@endif @if ($e->user)<span class="text-muted">({{ $e->user->name }})</span>@endif</span></div>
            @endforeach
        </div></div>
    </div>

    <div class="col-lg-5">
        @if ($next && auth()->user()->can('orders.update'))
            <div class="card mb-3"><div class="card-body">
                <h6 class="text-uppercase text-muted small">{{ __('Update status') }}</h6>
                <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                    @csrf
                    <select name="status" class="form-select mb-2">@foreach ($next as $s)<option value="{{ $s }}">{{ __(str_replace('_', ' ', ucfirst($s))) }}</option>@endforeach</select>
                    <input name="note" maxlength="190" class="form-control mb-2" placeholder="{{ __('Note (optional)') }}">
                    <button class="btn btn-primary w-100"><i class="bi bi-check2 me-1"></i> {{ __('Update') }}</button>
                </form>
            </div></div>
        @endif

        @if ($riders->isNotEmpty() && ! $order->is_pickup && $order->isOpen() && auth()->user()->can('orders.update'))
            <div class="card mb-3"><div class="card-body">
                <h6 class="text-uppercase text-muted small"><i class="bi bi-bicycle me-1"></i>{{ __('Rider') }}</h6>
                <form method="POST" action="{{ route('admin.orders.rider', $order) }}" class="d-flex gap-2">
                    @csrf
                    <select name="rider_id" class="form-select"><option value="">— {{ __('Unassigned') }} —</option>@foreach ($riders as $r)<option value="{{ $r->id }}" @selected($order->rider_id === $r->id)>{{ $r->name }}</option>@endforeach</select>
                    <button class="btn btn-outline-secondary">{{ __('Assign') }}</button>
                </form>
            </div></div>
        @elseif ($order->rider)
            <div class="card mb-3"><div class="card-body"><h6 class="text-uppercase text-muted small">{{ __('Rider') }}</h6><b>{{ $order->rider->name }}</b></div></div>
        @endif

        @if ($order->payment_status === 'unpaid' && $order->isOpen() && auth()->user()->can('orders.update'))
            <div class="card mb-3"><div class="card-body">
                <h6 class="text-uppercase text-muted small">{{ __('Payment received?') }}</h6>
                <form method="POST" action="{{ route('admin.orders.paid', $order) }}" class="d-flex gap-2">
                    @csrf
                    <input name="reference" maxlength="120" class="form-control" placeholder="{{ __('Reference (optional)') }}">
                    <button class="btn btn-outline-secondary text-nowrap">{{ __('Mark paid') }}</button>
                </form>
            </div></div>
        @endif

        <div class="card mb-3"><div class="card-body">
            <h6 class="text-uppercase text-muted small">{{ __('Customer') }}</h6>
            <div class="fw-semibold">{{ $order->name }} @if ($order->user)<span class="badge text-bg-secondary">{{ __('Account') }}</span>@endif</div>
            <div class="text-muted">{{ $order->phone }}<br>{{ $order->email }}</div>
            @php($digits = preg_replace('/\D/', '', $order->phone))
            @php($wa = str_starts_with((string) $order->phone, '+') ? $digits : (str_starts_with($digits, '0') ? setting('store.phone_code', '92').substr($digits, 1) : $digits))
            <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode(__('Hi :name, this is :store about your order :number.', ['name' => $order->name, 'store' => setting('store.name'), 'number' => $order->number])) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success mt-2" data-no-modal><i class="bi bi-whatsapp me-1"></i>{{ __('Message on WhatsApp') }}</a>
            @unless ($order->is_pickup)<div class="mt-2">{{ $order->address_line }}@if ($order->area), {{ $order->area }}@endif<br>{{ $order->city }} {{ $order->postal_code }}</div>@endunless
            @if ($order->notes)<div class="mt-2 small"><b>{{ __('Notes') }}:</b> {{ $order->notes }}</div>@endif
        </div></div>

        @if ($order->requires_prescription)
            <div class="card"><div class="card-body">
                <h6 class="text-uppercase text-muted small"><i class="bi bi-prescription2 me-1"></i>{{ __('Prescription') }}
                    <span class="badge rounded-pill text-bg-{{ ['approved' => 'success', 'rejected' => 'danger', 'pending' => 'warning'][$order->prescription_status] ?? 'secondary' }} ms-1">{{ __(ucfirst((string) $order->prescription_status)) }}</span></h6>
                @if ($order->prescription_note)<div class="small mb-2">{{ $order->prescription_note }}</div>@endif
                @if (\App\Support\BusinessProfile::hasModule('prescriptions') && auth()->user()->can('prescriptions.view'))<a href="{{ route('admin.prescriptions.show', $order) }}" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-clipboard2-check me-1"></i>{{ __('Review') }}</a>@endif
                @forelse ($order->getMedia('prescriptions') as $m)
                    <a href="{{ route('admin.orders.prescription', [$order, $m->id]) }}" target="_blank" class="d-block" data-no-modal><i class="bi bi-file-earmark-medical me-1"></i>{{ $m->file_name }}</a>
                @empty<span class="text-muted">{{ __('No file') }}</span>@endforelse
            </div></div>
        @endif
    </div>
</div>
@endsection
