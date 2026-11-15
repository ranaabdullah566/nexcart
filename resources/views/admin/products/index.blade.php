@extends('layouts.admin')
@section('title', __('Products'))

@section('content')
<x-page-header :title="__('Products')" :eyebrow="__('Catalog')" :sub="__('Everything you sell.')">
    <x-slot:actions>
        @can('products.view')<a href="{{ route('admin.products.export') }}" class="btn btn-outline-secondary" data-no-modal><i class="bi bi-download me-1"></i> {{ __('Export CSV') }}</a>@endcan
        @can('products.create')
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#importBox"><i class="bi bi-upload me-1"></i> {{ __('Import CSV') }}</button>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New product') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@can('products.create')
<div class="collapse mb-3" id="importBox"><div class="card"><div class="card-body">
    <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" data-no-ajax class="row g-2 align-items-end">
        @csrf
        <div class="col-md-6">
            <label class="form-label">{{ __('CSV file') }}</label>
            <input type="file" name="file" accept=".csv,text/csv" required class="form-control">
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i> {{ __('Import') }}</button></div>
        <div class="col-12 form-text">{{ __('Columns: sku, name, category, price, compare_price, cost, stock_qty, status, description. Rows with an existing SKU are updated. Variable products are managed in the product form.') }}</div>
    </form>
</div></div></div>
@endcan

<div class="row g-3 mb-4 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Products')" icon="box-seam-fill" :value="$total" :sub="__('In catalogue')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Active')" icon="check-circle-fill" tone="ok" :value="$activeCount" :sub="__('Visible in store')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Low stock')" icon="exclamation-triangle-fill" tone="warn" :value="$lowStock" :sub="__('At or below threshold')" /></div>
</div>

<div class="card"><div class="card-body">
    <table id="prodTable" class="table align-middle w-100" data-src="{{ route('admin.products.data') }}">
        <thead><tr><th>{{ __('Product') }}</th><th>{{ __('Category') }}</th><th>{{ __('Price') }}</th><th>{{ __('Stock') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody></tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">$(function () {
    var t = $('#prodTable');
    t.DataTable({
        serverSide: true, processing: true, responsive: true, pageLength: 25, lengthMenu: [10, 25, 50, 100], order: [], searchDelay: 400,
        ajax: { url: t.data('src'), type: 'GET' },
        columns: [{ data: 0, orderable: true }, { data: 1 }, { data: 2, className: 'text-nowrap' }, { data: 3 }, { data: 4 }, { data: 5, orderable: false, className: 'text-end' }],
        language: { search: "_INPUT_", searchPlaceholder: @json(__('Search name, SKU or barcode…')) },
        columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }]
    });
});</script>
@endpush
