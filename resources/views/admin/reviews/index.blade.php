@extends('layouts.admin')
@section('title', __('Reviews'))
@php($tabs = ['pending' => __('To approve'), 'approved' => __('Approved'), 'hidden' => __('Hidden'), 'all' => __('All')])
@section('content')
<x-page-header :title="__('Reviews')" :eyebrow="__('Growth')" :sub="__('What customers say about your products. Approve, hide or reply.')" />
<div class="row g-3 mb-4 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('To approve')" icon="hourglass-split" tone="warn" :value="$counts['pending'] ?? 0" :sub="__('Waiting for you')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Approved')" icon="chat-heart-fill" tone="ok" :value="$counts['approved'] ?? 0" :sub="__('Visible in the store')" /></div>
</div>
<ul class="nav nav-pills mb-3 gap-2 flex-wrap">@foreach ($tabs as $k => $l)<li class="nav-item"><a class="nav-link {{ $status === $k ? 'active' : '' }}" href="{{ route('admin.reviews.index', ['status' => $k]) }}" data-no-modal>{{ $l }}</a></li>@endforeach</ul>
<div class="card"><div class="card-body">
    <table id="rvTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('Product') }}</th><th>{{ __('Rating') }}</th><th>{{ __('Review') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($reviews as $r)
            <tr>
                <td class="fw-semibold">{{ $r->product?->name }}<div class="small text-muted">{{ $r->name }} · {{ local_time($r->created_at, 'd M Y') }}</div></td>
                <td data-order="{{ $r->rating }}"><span class="sf-stars" style="color:#fbbf24">@include('store._stars', ['value' => $r->rating])</span></td>
                <td style="max-width:420px"><b>{{ $r->title }}</b><div class="small text-muted">{{ \Illuminate\Support\Str::limit($r->body, 140) }}</div>@if ($r->reply)<div class="small"><i class="bi bi-reply-fill"></i> {{ \Illuminate\Support\Str::limit($r->reply, 80) }}</div>@endif</td>
                <td><span class="badge rounded-pill text-bg-{{ ['approved' => 'success', 'pending' => 'warning', 'hidden' => 'secondary'][$r->status] }}">{{ __(ucfirst($r->status)) }}</span></td>
                <td class="text-end"><x-row-actions :edit="route('admin.reviews.edit', $r)" :delete="route('admin.reviews.destroy', $r)" :confirm="__('Delete this review?')" /></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection
@push('scripts')
<script nonce="{{ csp_nonce() }}">$(function () { $('#rvTable').DataTable({ responsive: true, pageLength: 10, order: [], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search…')) }, columnDefs: [{ orderable: false, targets: -1 }] }); });</script>
@endpush
