@extends('layouts.admin')
@section('title', __('Product fields'))

@section('content')
<x-page-header :title="__('Product fields')" :eyebrow="__('Catalog')" :sub="__('Extra details for your products — e.g. composition for medicines, spice level for food.')">
    <x-slot:actions>
        <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New field') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="card"><div class="card-body">
    <table id="attrTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Label') }}</th><th>{{ __('Key') }}</th><th>{{ __('Type') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($attributes as $a)
            <tr>
                <td class="fw-semibold">{{ $a->label }} @if ($a->is_required)<span class="text-danger">*</span>@endif</td>
                <td class="figure text-muted">{{ $a->key }}</td>
                <td><span class="badge rounded-pill text-bg-secondary">{{ __(ucfirst($a->type)) }}</span></td>
                <td class="text-end"><x-row-actions :edit="route('admin.attributes.edit', $a)" :delete="route('admin.attributes.destroy', $a)" :confirm="__('Delete this field?')" /></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">$(function () { $('#attrTable').DataTable({ responsive: true, pageLength: 25, order: [], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search fields…')) }, columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { orderable: false, targets: -1 }] }); });</script>
@endpush
