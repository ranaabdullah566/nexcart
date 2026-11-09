@extends('layouts.admin')
@section('title', __('Prescription requests'))

@php($tabs = ['new' => __('New'), 'contacted' => __('Called back'), 'closed' => __('Closed'), 'all' => __('All')])
@section('content')
<x-page-header :title="__('Prescription requests')" :eyebrow="__('Pharmacy')" :sub="__('Customers who sent a prescription photo without ordering. Call them back and help them order.')" />

<div class="row g-3 mb-4 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('New')" icon="camera-fill" tone="warn" :value="$counts['new'] ?? 0" :sub="__('Waiting for a call')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Called back')" icon="telephone-fill" tone="ok" :value="$counts['contacted'] ?? 0" :sub="__('Customer contacted')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Closed')" icon="check2-circle" tone="info" :value="$counts['closed'] ?? 0" :sub="__('Done')" /></div>
</div>

<ul class="nav nav-pills mb-3 gap-2 flex-wrap">@foreach ($tabs as $k => $l)<li class="nav-item"><a class="nav-link {{ $status === $k ? 'active' : '' }}" href="{{ route('admin.prescription-requests.index', ['status' => $k]) }}">{{ $l }}</a></li>@endforeach</ul>

<div class="card"><div class="card-body">
    <table id="rqTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Customer') }}</th><th>{{ __('Files') }}</th><th>{{ __('Note') }}</th><th>{{ __('Status') }}</th><th>{{ __('Received') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($requests as $r)
            <tr>
                <td><div class="fw-semibold">{{ $r->name }}</div><a class="small figure text-decoration-none" href="tel:{{ preg_replace('/[^0-9+]/', '', $r->phone) }}"><i class="bi bi-telephone me-1"></i>{{ $r->phone }}</a>@if ($r->city)<div class="small text-muted">{{ $r->city }}</div>@endif</td>
                <td>@foreach ($r->getMedia('files') as $m)<a class="btn btn-sm btn-outline-secondary me-1 mb-1" target="_blank" rel="noopener" href="{{ route('admin.prescription-requests.file', [$r, $m->id]) }}"><i class="bi bi-file-earmark-medical"></i> {{ $loop->iteration }}</a>@endforeach</td>
                <td class="small" style="max-width:260px">{{ $r->notes }}</td>
                <td><span class="badge rounded-pill text-bg-{{ ['new' => 'warning', 'contacted' => 'success', 'closed' => 'secondary'][$r->status] ?? 'secondary' }}">{{ $tabs[$r->status] ?? $r->status }}</span>@if ($r->handler)<div class="small text-muted">{{ $r->handler->name }}</div>@endif</td>
                <td class="small text-muted text-nowrap" data-order="{{ $r->created_at->timestamp }}">{{ local_time($r->created_at, 'd M, H:i') }}</td>
                <td class="text-end">
                    @can('prescriptions.verify')
                    <div class="d-inline-flex gap-1">
                        @if ($r->status !== 'contacted')<form method="POST" action="{{ route('admin.prescription-requests.update', $r) }}" data-no-ajax>@csrf @method('PUT')<input type="hidden" name="status" value="contacted"><button class="btn btn-sm btn-outline-success" title="{{ __('Mark as called back') }}"><i class="bi bi-telephone-outbound"></i></button></form>@endif
                        @if ($r->status !== 'closed')<form method="POST" action="{{ route('admin.prescription-requests.update', $r) }}" data-no-ajax>@csrf @method('PUT')<input type="hidden" name="status" value="closed"><button class="btn btn-sm btn-outline-secondary" title="{{ __('Close') }}"><i class="bi bi-check2-circle"></i></button></form>@endif
                        <x-row-actions :delete="route('admin.prescription-requests.destroy', $r)" :confirm="__('Delete this request?')" />
                    </div>
                    @endcan
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">$(function () { $('#rqTable').DataTable({ responsive: true, pageLength: 10, order: [[4, 'asc']], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search…')) } }); });</script>
@endpush
