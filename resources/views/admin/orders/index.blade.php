@extends('layouts.admin')
@section('title', __('Orders'))

@php($tabs = ['' => __('All'), 'open' => __('Open'), 'pending' => __('Pending'), 'preparing' => __('Preparing'), 'completed' => __('Completed'), 'cancelled' => __('Cancelled')])
@section('content')
<x-page-header :title="__('Orders')" :eyebrow="__('Sales')" :sub="__('Everything customers have ordered. Click an order to manage it.')" />

<div class="row g-3 mb-4 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Open orders')" icon="hourglass-split" tone="warn" :value="$open" :sub="__('Need attention')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Pending')" icon="inbox-fill" :value="$counts['pending'] ?? 0" :sub="__('Waiting for confirmation')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Completed')" icon="check-circle-fill" tone="ok" :value="$counts['completed'] ?? 0" :sub="__('Delivered or picked up')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Cancelled')" icon="x-octagon-fill" tone="bad" :value="($counts['cancelled'] ?? 0) + ($counts['refunded'] ?? 0)" :sub="__('Including refunds')" /></div>
</div>

<ul class="nav nav-pills mb-3 gap-2 flex-wrap">
    @foreach ($tabs as $k => $label)<li class="nav-item"><a class="nav-link {{ ($status ?? '') === $k ? 'active' : '' }}" href="{{ route('admin.orders.index', $k ? ['status' => $k] : []) }}" data-no-modal>{{ $label }}</a></li>@endforeach
</ul>

<div class="card"><div class="card-body">
    <table id="orderTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Order') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Total') }}</th><th>{{ __('Payment') }}</th><th>{{ __('Status') }}</th><th>{{ __('Placed') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($orders as $o)
            <tr>
                <td><a href="{{ route('admin.orders.show', $o) }}" class="fw-semibold figure text-decoration-none">{{ $o->number }}</a><div class="small text-muted">{{ trans_choice(':n item|:n items', $o->items_count, ['n' => $o->items_count]) }} · {{ $o->is_pickup ? __('Pickup') : __('Delivery') }}</div></td>
                <td><div class="fw-semibold">{{ $o->name }}</div><div class="small text-muted">{{ $o->phone }}</div></td>
                <td class="figure fw-bold text-nowrap" data-order="{{ $o->total }}">{{ money($o->total) }}</td>
                <td><span class="badge rounded-pill text-bg-{{ ['paid' => 'success', 'unpaid' => 'warning', 'failed' => 'danger', 'refunded' => 'secondary'][$o->payment_status] }}">{{ __(ucfirst($o->payment_status)) }}</span> <span class="small text-muted">{{ ['cod' => 'COD', 'bank' => __('Bank'), 'stripe' => __('Card')][$o->payment_method] ?? $o->payment_method }}</span></td>
                <td><span class="badge rounded-pill text-bg-{{ ['pending' => 'warning', 'confirmed' => 'info', 'preparing' => 'info', 'out_for_delivery' => 'primary', 'ready' => 'primary', 'completed' => 'success', 'cancelled' => 'danger', 'refunded' => 'secondary'][$o->status] }}">{{ $o->statusLabel() }}</span></td>
                <td class="small text-muted text-nowrap" data-order="{{ $o->placed_at?->timestamp }}">{{ $o->placed_at?->format('d M, H:i') }}</td>
                <td class="text-end"><x-row-actions :show="route('admin.orders.show', $o)" /></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script>$(function () { $('#orderTable').DataTable({ responsive: true, pageLength: 10, order: [[5, 'desc']], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search orders…')) }, columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { orderable: false, targets: -1 }] }); });</script>
@endpush
