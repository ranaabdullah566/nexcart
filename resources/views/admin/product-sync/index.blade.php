@extends('layouts.admin')
@section('title', __('Product sync'))

@section('content')
<x-page-header :title="__('Product sync')" :eyebrow="__('Catalog')" :sub="__('Upload your weekly product sheet. New SKUs are added, existing ones updated.')">
    <x-slot:actions>
        <a href="{{ route('admin.product-sync.export') }}" class="btn btn-outline-secondary" data-no-modal><i class="bi bi-download me-1"></i> {{ __('Download current sheet') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Synced products')" icon="box-seam-fill" :value="$total" :sub="__('Created by the sheet')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Last sync')" icon="arrow-repeat" :value="$last ? $last->created_at->diffForHumans() : '—'" :sub="$last?->filename" /></div>
</div>

@can('products.create')
<div class="card mb-4"><div class="card-body">
    <form method="POST" action="{{ route('admin.product-sync.store') }}" enctype="multipart/form-data" data-no-ajax class="row g-3">
        @csrf
        <div class="col-lg-6">
            <label class="form-label">{{ __('Product sheet (.xlsx or .csv)') }}</label>
            <input type="file" name="file" accept=".xlsx,.csv" required class="form-control">
            <div class="form-text">{{ __('Columns: sku, name, price, active, maximumSalesQuantity, isWeighted, baseWeight, baseWeightUnit, pricePerBaseUnit, barcode 1–5, category 1. The SKU is the key.') }}</div>
        </div>
        <div class="col-lg-6">
            <div class="form-check"><input class="form-check-input" type="checkbox" name="update_existing" value="1" id="o1" checked><label class="form-check-label" for="o1">{{ __('Update products that already exist') }}</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="use_active" value="1" id="o2" checked><label class="form-check-label" for="o2">{{ __('Use the sheet\'s active column for visibility') }}</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="deactivate_missing" value="1" id="o3"><label class="form-check-label" for="o3">{{ __('Hide synced products that are missing from this sheet') }}</label></div>
        </div>
        <div class="col-12"><button class="btn btn-primary"><i class="bi bi-upload me-1"></i> {{ __('Upload & sync') }}</button> <span class="text-muted small ms-2">{{ __('Large sheets can take a minute.') }}</span></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body">
    <h6 class="mb-3">{{ __('Sync history') }}</h6>
    <div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>{{ __('When') }}</th><th>{{ __('File') }}</th><th>{{ __('Rows') }}</th><th>{{ __('Added') }}</th><th>{{ __('Updated') }}</th><th>{{ __('Unchanged') }}</th><th>{{ __('Skipped') }}</th><th>{{ __('By') }}</th><th></th></tr></thead>
        <tbody>
        @forelse ($runs as $r)
            <tr><td>{{ $r->created_at->format('d M Y H:i') }}</td><td>{{ $r->filename }}</td><td>{{ $r->rows }}</td><td>{{ $r->created }}</td><td>{{ $r->updated }}</td><td>{{ $r->unchanged }}</td><td>{{ $r->skipped }}</td><td>{{ $r->user?->name }}</td>
                <td class="text-end"><a href="{{ route('admin.product-sync.show', $r) }}" class="btn btn-sm btn-outline-secondary">{{ __('Details') }}</a></td></tr>
        @empty
            <tr><td colspan="9" class="text-muted text-center py-4">{{ __('No syncs yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div></div>
@endsection
