@extends('layouts.admin')
@section('title', __('Pages'))
@section('content')
<x-page-header :title="__('Pages')" :eyebrow="__('Storefront')" :sub="__('About us, delivery info, privacy policy — shown at /page/your-page.')">
    <x-slot:actions><a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New page') }}</a></x-slot:actions>
</x-page-header>
<div class="card"><div class="card-body">
    <table id="pgTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Title') }}</th><th>{{ __('Address') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($pages as $p)
            <tr><td class="fw-semibold">{{ $p->title }}</td><td class="figure text-muted">/page/{{ $p->slug }}</td>
                <td><span class="badge rounded-pill text-bg-{{ $p->is_published ? 'success' : 'secondary' }}">{{ $p->is_published ? __('Published') : __('Draft') }}</span></td>
                <td class="text-end"><a href="{{ route('store.page', $p->slug) }}" target="_blank" class="btn btn-sm view" data-no-modal title="{{ __('View') }}"><i class="bi bi-box-arrow-up-right"></i></a>
                    <x-row-actions :edit="route('admin.pages.edit', $p)" :delete="route('admin.pages.destroy', $p)" :confirm="__('Delete this page?')" /></td></tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection
@push('scripts')
<script nonce="{{ csp_nonce() }}">$(function () { $('#pgTable').DataTable({ responsive: true, pageLength: 25, order: [[0, 'asc']], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search…')) }, columnDefs: [{ orderable: false, targets: -1 }] }); });</script>
@endpush
