@extends('layouts.admin')
@section('title', __('Customers'))

@section('content')
<x-page-header :title="__('Customers')" :eyebrow="__('Sales')" :sub="__('Everyone who has ordered, guests included.')" />
<div class="card"><div class="card-body">
    <table id="custTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Customer') }}</th><th>{{ __('Orders') }}</th><th>{{ __('Spent') }}</th><th>{{ __('Last order') }}</th></tr></thead>
        <tbody>
        @foreach ($customers as $c)
            <tr>
                <td><div class="d-flex align-items-center gap-3"><span class="nx-chip-avatar">{{ mb_strtoupper(mb_substr($c->name, 0, 1)) }}</span><div><div class="fw-semibold">{{ $c->name }} @if ($c->has_account)<span class="badge text-bg-secondary">{{ __('Account') }}</span>@endif</div><div class="small text-muted">{{ $c->email }} · {{ $c->phone }}</div></div></div></td>
                <td class="figure">{{ $c->orders }}</td>
                <td class="figure fw-bold" data-order="{{ $c->spent }}">{{ money($c->spent) }}</td>
                <td class="small text-muted text-nowrap" data-order="{{ \Illuminate\Support\Carbon::parse($c->last_order)->timestamp }}">{{ \Illuminate\Support\Carbon::parse($c->last_order)->format('d M Y') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script>$(function () { $('#custTable').DataTable({ responsive: true, pageLength: 10, order: [[2, 'desc']], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search customers…')) }, columnDefs: [{ responsivePriority: 1, targets: 0 }] }); });</script>
@endpush
