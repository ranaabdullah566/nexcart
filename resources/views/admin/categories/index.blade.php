@extends('layouts.admin')
@section('title', __('Categories'))

@section('content')
<x-page-header :title="__('Categories')" :eyebrow="__('Catalog')" :sub="__('Group medicines in up to 3 levels, e.g. Pain & fever › Pain relief › Tablets.')">
    <x-slot:actions>
        @can('categories.create')<a href="{{ route('admin.categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New category') }}</a>@endcan
    </x-slot:actions>
</x-page-header>

<div class="card"><div class="card-body">
    <table id="catTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Products') }}</th><th>{{ __('Order') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($categories as $c)
            <tr>
                <td><div class="d-flex align-items-center gap-3"><span class="nx-chip-avatar"><i class="bi bi-diagram-3"></i></span><span class="fw-semibold">{{ $c->name }}</span></div></td>
                <td class="figure">{{ $c->products_count }}</td>
                <td class="figure text-muted">{{ $c->sort_order }}</td>
                <td><span class="badge rounded-pill text-bg-{{ $c->is_active ? 'success' : 'secondary' }}">{{ $c->is_active ? __('Active') : __('Hidden') }}</span></td>
                <td class="text-end"><x-row-actions :edit="route('admin.categories.edit', $c)" :delete="route('admin.categories.destroy', $c)" :can="['edit' => 'categories.update', 'delete' => 'categories.delete']" :confirm="__('Delete this category? Its products stay, uncategorised.')" /></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">$(function () { $('#catTable').DataTable({ responsive: true, pageLength: 25, order: [], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search categories…')) }, columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { orderable: false, targets: -1 }] }); });</script>
@endpush
