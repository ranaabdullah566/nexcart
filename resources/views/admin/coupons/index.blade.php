@extends('layouts.admin')
@section('title', __('Coupons'))

@section('content')
<x-page-header :title="__('Coupons')" :eyebrow="__('Sales')" :sub="__('Discount codes customers enter at checkout.')">
    <x-slot:actions><a href="{{ route('admin.coupons.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New coupon') }}</a></x-slot:actions>
</x-page-header>

<div class="card"><div class="card-body">
    <table id="cpTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Discount') }}</th><th>{{ __('Used') }}</th><th>{{ __('Valid') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($coupons as $c)
            @php($live = $c->is_active && (! $c->ends_at || $c->ends_at->isFuture()) && (! $c->starts_at || $c->starts_at->isPast()) && ($c->usage_limit === null || $c->used_count < $c->usage_limit))
            <tr>
                <td class="fw-bold figure">{{ $c->code }}</td>
                <td>{{ $c->label() }} @if ($c->min_subtotal)<div class="small text-muted">{{ __('Min :amount', ['amount' => money($c->min_subtotal)]) }}</div>@endif</td>
                <td class="figure">{{ $c->used_count }}@if ($c->usage_limit) / {{ $c->usage_limit }}@endif</td>
                <td class="small text-muted">{{ $c->starts_at?->format('d M Y') ?? '—' }} → {{ $c->ends_at?->format('d M Y') ?? '∞' }}</td>
                <td><span class="badge rounded-pill text-bg-{{ $live ? 'success' : 'secondary' }}">{{ $live ? __('Live') : __('Inactive') }}</span></td>
                <td class="text-end"><x-row-actions :edit="route('admin.coupons.edit', $c)" :delete="route('admin.coupons.destroy', $c)" :confirm="__('Delete this coupon?')" /></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">$(function () { $('#cpTable').DataTable({ responsive: true, pageLength: 25, order: [], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search coupons…')) }, columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { orderable: false, targets: -1 }] }); });</script>
@endpush
