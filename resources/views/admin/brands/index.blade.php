@extends('layouts.admin')
@section('title', __('Brands'))

@section('content')
<x-page-header :title="__('Brands')" :eyebrow="__('Catalog')" :sub="__('Manufacturers and labels your products belong to.')">
    <x-slot:actions>
        @can('products.create')<a href="{{ route('admin.brands.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New brand') }}</a>@endcan
    </x-slot:actions>
</x-page-header>

<div class="card"><div class="card-body">
    <table id="brandTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Products') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($brands as $b)
            <tr>
                <td><div class="d-flex align-items-center gap-3"><span class="nx-chip-avatar"><i class="bi bi-award"></i></span><span class="fw-semibold">{{ $b->name }}</span></div></td>
                <td class="figure">{{ $b->products_count }}</td>
                <td><span class="badge rounded-pill text-bg-{{ $b->is_active ? 'success' : 'secondary' }}">{{ $b->is_active ? __('Active') : __('Hidden') }}</span></td>
                <td class="text-end"><x-row-actions :edit="route('admin.brands.edit', $b)" :delete="route('admin.brands.destroy', $b)" :can="['edit' => 'products.update', 'delete' => 'products.delete']" :confirm="__('Delete this brand?')" /></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script>$(function () { $('#brandTable').DataTable({ responsive: true, pageLength: 25, order: [[0, 'asc']], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search brands…')) }, columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { orderable: false, targets: -1 }] }); });</script>
@endpush
