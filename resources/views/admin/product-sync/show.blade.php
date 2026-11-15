@extends('layouts.admin')
@section('title', __('Product sync'))

@section('content')
<x-page-header :title="$run->filename" :eyebrow="__('Product sync')" :sub="$run->created_at->format('d M Y H:i').' · '.number_format($run->duration_ms / 1000, 1).'s'">
    <x-slot:actions><a href="{{ route('admin.product-sync.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> {{ __('Back') }}</a></x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4 stagger">
    <div class="col-6 col-lg-2"><x-stat-card :label="__('Rows')" icon="list-ol" :value="$run->rows" /></div>
    <div class="col-6 col-lg-2"><x-stat-card :label="__('Added')" icon="plus-circle-fill" tone="ok" :value="$run->created" /></div>
    <div class="col-6 col-lg-2"><x-stat-card :label="__('Updated')" icon="arrow-repeat" :value="$run->updated" /></div>
    <div class="col-6 col-lg-2"><x-stat-card :label="__('Unchanged')" icon="dash-circle" :value="$run->unchanged" /></div>
    <div class="col-6 col-lg-2"><x-stat-card :label="__('Skipped')" icon="exclamation-triangle-fill" tone="warn" :value="$run->skipped" /></div>
    <div class="col-6 col-lg-2"><x-stat-card :label="__('Hidden')" icon="eye-slash-fill" :value="$run->deactivated" :sub="$run->categories_created.' '.__('new categories')" /></div>
</div>

@if ($run->errors)
<div class="card"><div class="card-body">
    <h6 class="mb-3">{{ __('Skipped rows') }}</h6>
    <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>{{ __('Row') }}</th><th>{{ __('SKU') }}</th><th>{{ __('Reason') }}</th></tr></thead>
        <tbody>@foreach ($run->errors as $e)<tr><td>{{ $e['row'] }}</td><td>{{ $e['sku'] }}</td><td>{{ $e['error'] }}</td></tr>@endforeach</tbody>
    </table></div>
</div></div>
@endif
@endsection
