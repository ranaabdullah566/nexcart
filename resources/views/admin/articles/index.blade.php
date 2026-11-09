@extends('layouts.admin')
@section('title', __('Health blog'))
@section('content')
<x-page-header :title="__('Health blog')" :eyebrow="__('Storefront')" :sub="__('Tips and guidance for your customers — shown at /blog and on the home page.')">
    <x-slot:actions><a href="{{ route('admin.articles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New article') }}</a></x-slot:actions>
</x-page-header>
<div class="card"><div class="card-body">
    <table id="arTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Title') }}</th><th>{{ __('Topic') }}</th><th>{{ __('Published') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($articles as $a)
            @php $scheduled = $a->is_published && $a->published_at && $a->published_at->isFuture(); @endphp
            <tr><td class="fw-semibold">{{ $a->title }}<div class="small text-muted figure">/blog/{{ $a->slug }}</div></td>
                <td>{{ $a->tag }}</td>
                <td class="small text-muted text-nowrap" data-order="{{ $a->date()->timestamp }}">{{ local_time($a->date(), 'd M Y, H:i') }}</td>
                <td><span class="badge rounded-pill text-bg-{{ $scheduled ? 'info' : ($a->is_published ? 'success' : 'secondary') }}">{{ $scheduled ? __('Scheduled') : ($a->is_published ? __('Published') : __('Draft')) }}</span></td>
                <td class="text-end"><a href="{{ route('store.article', $a->slug) }}" target="_blank" class="btn btn-sm view" data-no-modal title="{{ __('View') }}"><i class="bi bi-box-arrow-up-right"></i></a>
                    <x-row-actions :edit="route('admin.articles.edit', $a)" :delete="route('admin.articles.destroy', $a)" :confirm="__('Delete this article?')" /></td></tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection
@push('scripts')
<script nonce="{{ csp_nonce() }}">$(function () { $('#arTable').DataTable({ responsive: true, pageLength: 25, order: [[2, 'desc']], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search…')) }, columnDefs: [{ orderable: false, targets: -1 }] }); });</script>
@endpush
