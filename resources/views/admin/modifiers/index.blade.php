@extends('layouts.admin')
@section('title', __('Add-ons'))

@section('content')
<x-page-header :title="__('Add-ons')" :eyebrow="__('Catalog')" :sub="__('Choices customers can add to an item — size, toppings, extras.')">
    <x-slot:actions>
        <a href="{{ route('admin.modifier-groups.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New add-on group') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card"><div class="card-body">
    <table id="modTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Group') }}</th><th>{{ __('Options') }}</th><th>{{ __('Choose') }}</th><th>{{ __('Used on') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($groups as $g)
            <tr>
                <td class="fw-semibold">{{ $g->name }}</td>
                <td class="small text-muted">{{ $g->options->pluck('name')->take(5)->implode(', ') }}@if ($g->options->count() > 5) +{{ $g->options->count() - 5 }}@endif</td>
                <td class="figure">{{ $g->min_select }}–{{ $g->max_select }}</td>
                <td class="figure">{{ $g->products_count }}</td>
                <td class="text-end"><x-row-actions :edit="route('admin.modifier-groups.edit', $g)" :delete="route('admin.modifier-groups.destroy', $g)" :confirm="__('Delete this add-on group?')" /></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script>$(function () { $('#modTable').DataTable({ responsive: true, pageLength: 25, order: [[0, 'asc']], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search add-ons…')) }, columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { orderable: false, targets: -1 }] }); });</script>
@endpush
