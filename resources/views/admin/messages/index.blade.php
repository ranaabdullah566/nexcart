@extends('layouts.admin')
@section('title', __('Messages'))

@php($tabs = ['new' => __('New'), 'read' => __('Read'), 'replied' => __('Replied'), 'all' => __('All')])
@section('content')
<x-page-header :title="__('Messages')" :eyebrow="__('Storefront')" :sub="__('Messages sent from the Contact page, and your newsletter list.')">
    <x-slot:actions><a href="{{ route('admin.messages.subscribers') }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i> {{ __('Subscribers') }} ({{ $subscribers }})</a></x-slot:actions>
</x-page-header>

<ul class="nav nav-pills mb-3 gap-2 flex-wrap">@foreach ($tabs as $k => $l)<li class="nav-item"><a class="nav-link {{ $status === $k ? 'active' : '' }}" href="{{ route('admin.messages.index', ['status' => $k]) }}">{{ $l }}@if ($k !== 'all' && ($counts[$k] ?? 0)) <span class="badge text-bg-light ms-1">{{ $counts[$k] }}</span>@endif</a></li>@endforeach</ul>

<div class="card"><div class="card-body">
    <table id="msgTable" class="table align-middle w-100">
        <thead><tr><th>{{ __('From') }}</th><th>{{ __('Message') }}</th><th>{{ __('Status') }}</th><th>{{ __('Received') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($messages as $m)
            <tr>
                <td><div class="fw-semibold">{{ $m->name }}</div><a class="small text-decoration-none" href="mailto:{{ $m->email }}">{{ $m->email }}</a>@if ($m->phone)<div class="small figure text-muted">{{ $m->phone }}</div>@endif</td>
                <td style="max-width:420px">@if ($m->subject)<div class="fw-semibold">{{ $m->subject }}</div>@endif<div class="small" style="white-space:pre-line">{{ $m->message }}</div></td>
                <td><span class="badge rounded-pill text-bg-{{ ['new' => 'warning', 'read' => 'info', 'replied' => 'success'][$m->status] ?? 'secondary' }}">{{ $tabs[$m->status] ?? $m->status }}</span></td>
                <td class="small text-muted text-nowrap" data-order="{{ $m->created_at->timestamp }}">{{ local_time($m->created_at, 'd M, H:i') }}</td>
                <td class="text-end"><div class="d-inline-flex gap-1">
                    <a class="btn btn-sm btn-outline-primary" href="mailto:{{ $m->email }}?subject={{ rawurlencode('Re: '.($m->subject ?: __('Your message'))) }}" title="{{ __('Reply by email') }}"><i class="bi bi-reply"></i></a>
                    @foreach (['read' => ['eye', 'Mark as read'], 'replied' => ['check2-all', 'Mark as replied']] as $st => [$ic, $tt])
                        @if ($m->status !== $st)<form method="POST" action="{{ route('admin.messages.update', $m) }}" data-no-ajax>@csrf @method('PUT')<input type="hidden" name="status" value="{{ $st }}"><button class="btn btn-sm btn-outline-secondary" title="{{ __($tt) }}"><i class="bi bi-{{ $ic }}"></i></button></form>@endif
                    @endforeach
                    <x-row-actions :delete="route('admin.messages.destroy', $m)" :confirm="__('Delete this message?')" />
                </div></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">$(function () { $('#msgTable').DataTable({ responsive: true, pageLength: 10, order: [[3, 'asc']], language: { search: "_INPUT_", searchPlaceholder: @json(__('Search…')) } }); });</script>
@endpush
