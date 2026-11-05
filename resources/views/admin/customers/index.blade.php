@extends('layouts.admin')
@section('title', __('Customers'))

@section('content')
<x-page-header :title="__('Customers')" :eyebrow="__('Sales')" :sub="__('Shop accounts and guests who have ordered.')">
    <x-slot:actions>@can('customers.create')<a href="{{ route('admin.customers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New customer') }}</a>@endcan</x-slot:actions>
</x-page-header>
<div class="card"><div class="card-body">
    <table id="custTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Customer') }}</th><th>{{ __('Orders') }}</th><th>{{ __('Spent') }}</th><th>{{ __('Last order') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($customers as $c)
            <tr>
                <td><div class="d-flex align-items-center gap-3"><span class="nx-chip-avatar">{{ mb_strtoupper(mb_substr($c->name, 0, 1)) }}</span><div><div class="fw-semibold">{{ $c->name }} @if ($c->has_account)<span class="badge text-bg-secondary">{{ __('Account') }}</span>@endif</div><div class="small text-muted">{{ $c->email }} · {{ $c->phone }}</div></div></div></td>
                <td class="figure">{{ $c->orders }}</td>
                <td class="figure fw-bold" data-order="{{ $c->spent }}">{{ money($c->spent) }}</td>
                <td class="small text-muted text-nowrap" data-order="{{ $c->last_order ? \Illuminate\Support\Carbon::parse($c->last_order)->timestamp : 0 }}">{{ $c->last_order ? \Illuminate\Support\Carbon::parse($c->last_order)->format('d M Y') : '—' }}</td>
                <td class="text-end">@if ($c->id)<x-row-actions :edit="route('admin.customers.edit', $c->id)" :delete="route('admin.customers.destroy', $c->id)" :can="['edit' => 'customers.update', 'delete' => 'customers.delete']" :confirm="__('Delete this customer?')" />@else @can('customers.create')<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.customers.create', ['name' => $c->name, 'email' => $c->email, 'phone' => $c->phone]) }}">{{ __('Create account') }}</a>@endcan @endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">$(function () { $('#custTable').DataTable({ responsive: true, pageLength: 10, order: [[2, 'desc']], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search customers…')) }, columnDefs: [{ responsivePriority: 1, targets: 0 }, { orderable: false, targets: -1 }] }); });</script>
@endpush
