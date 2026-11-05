@extends('layouts.admin')
@section('title', __('Delivery slots'))

@section('content')
<x-page-header :title="__('Delivery slots')" :eyebrow="__('Sales')" :sub="__('Time windows customers pick at checkout, with a limit on orders per window.')">
    <x-slot:actions><a href="{{ route('admin.delivery-slots.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New time slot') }}</a></x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body">
    <form method="POST" action="{{ route('admin.delivery-slots.settings') }}" data-no-ajax class="row g-3 align-items-end">
        @csrf
        <div class="col-md-4"><label class="form-label">{{ __('How many days ahead can customers book?') }}</label><input type="number" min="0" max="14" name="days_ahead" value="{{ old('days_ahead', $daysAhead) }}" class="form-control figure"></div>
        <div class="col-md-4"><label class="form-label">{{ __('Close a slot this many minutes before it starts') }}</label><input type="number" min="0" max="1440" name="cutoff_minutes" value="{{ old('cutoff_minutes', $cutoff) }}" class="form-control figure"></div>
        <div class="col-md-4"><button class="btn btn-outline-secondary w-100"><i class="bi bi-check2 me-1"></i> {{ __('Save settings') }}</button></div>
    </form>
</div></div>

<div class="card"><div class="card-body">
    <table id="slotTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Time') }}</th><th>{{ __('Orders per day') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($slots as $s)
            <tr>
                <td class="fw-semibold">{{ $s->label }}</td><td class="figure">{{ $s->range() }}</td><td class="figure">{{ $s->capacity ?? '∞' }}</td>
                <td><span class="badge rounded-pill text-bg-{{ $s->is_active ? 'success' : 'secondary' }}">{{ $s->is_active ? __('Active') : __('Hidden') }}</span></td>
                <td class="text-end"><x-row-actions :edit="route('admin.delivery-slots.edit', $s)" :delete="route('admin.delivery-slots.destroy', $s)" :confirm="__('Delete this time slot?')" /></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">$(function () { $('#slotTable').DataTable({ responsive: true, pageLength: 25, order: [[1, 'asc']], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search…')) }, columnDefs: [{ orderable: false, targets: -1 }] }); });</script>
@endpush
