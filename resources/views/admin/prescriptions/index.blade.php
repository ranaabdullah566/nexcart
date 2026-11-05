@extends('layouts.admin')
@section('title', __('Prescriptions'))

@php($tabs = ['pending' => __('To review'), 'approved' => __('Approved'), 'rejected' => __('Sent back'), 'all' => __('All')])
@section('content')
<x-page-header :title="__('Prescriptions')" :eyebrow="__('Pharmacy')" :sub="__('Check each prescription before the medicines are prepared.')" />

<div class="row g-3 mb-4 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('To review')" icon="hourglass-split" tone="warn" :value="$counts['pending'] ?? 0" :sub="__('Waiting for a pharmacist')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Approved')" icon="patch-check-fill" tone="ok" :value="$counts['approved'] ?? 0" :sub="__('Ready to prepare')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Sent back')" icon="arrow-return-left" tone="bad" :value="$counts['rejected'] ?? 0" :sub="__('Waiting for a new upload')" /></div>
</div>

<ul class="nav nav-pills mb-3 gap-2 flex-wrap">@foreach ($tabs as $k => $l)<li class="nav-item"><a class="nav-link {{ $status === $k ? 'active' : '' }}" href="{{ route('admin.prescriptions.index', ['status' => $k]) }}" data-no-modal>{{ $l }}</a></li>@endforeach</ul>

<div class="card"><div class="card-body">
    <table id="rxTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Order') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Files') }}</th><th>{{ __('Status') }}</th><th>{{ __('Placed') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($orders as $o)
            <tr>
                <td><a href="{{ route('admin.prescriptions.show', $o) }}" class="fw-semibold figure text-decoration-none">{{ $o->number }}</a><div class="small text-muted">{{ trans_choice(':n item|:n items', $o->items_count, ['n' => $o->items_count]) }}</div></td>
                <td><div class="fw-semibold">{{ $o->name }}</div><div class="small text-muted">{{ $o->phone }}</div></td>
                <td class="figure">{{ $o->getMedia('prescriptions')->count() }}</td>
                <td><span class="badge rounded-pill text-bg-{{ ['approved' => 'success', 'rejected' => 'danger', 'pending' => 'warning'][$o->prescription_status] ?? 'secondary' }}">{{ __(ucfirst((string) $o->prescription_status)) }}</span>@if ($o->prescriptionReviewer)<div class="small text-muted">{{ $o->prescriptionReviewer->name }}</div>@endif</td>
                <td class="small text-muted text-nowrap" data-order="{{ $o->placed_at?->timestamp }}">{{ local_time($o->placed_at, 'd M, H:i') }}</td>
                <td class="text-end"><x-row-actions :show="route('admin.prescriptions.show', $o)" /></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script>$(function () { $('#rxTable').DataTable({ responsive: true, pageLength: 10, order: [[4, 'asc']], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search…')) }, columnDefs: [{ responsivePriority: 1, targets: 0 }, { orderable: false, targets: -1 }] }); });</script>
@endpush
