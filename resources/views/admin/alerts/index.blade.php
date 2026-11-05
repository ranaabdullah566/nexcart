@extends('layouts.admin')
@section('title', __('Alerts'))

@section('content')
<x-page-header :title="__('Alerts')" :eyebrow="__('Your inbox')" :sub="__('Every notification the system showed you, new orders and low stock — kept here.')">
    <x-slot:actions>
        @if ($unread)<form method="POST" action="{{ route('admin.alerts.read-all') }}" data-no-ajax>@csrf<button class="btn btn-outline-secondary"><i class="bi bi-check2-all me-1"></i> {{ __('Mark all read') }}</button></form>@endif
        @if ($alerts->total())<form method="POST" action="{{ route('admin.alerts.clear') }}" data-no-ajax data-confirm="{{ __('Delete all your alerts?') }}" data-confirm-note="{{ __('This cannot be undone.') }}">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i> {{ __('Clear all') }}</button></form>@endif
    </x-slot:actions>
</x-page-header>

<ul class="nav nav-pills mb-3 gap-2 flex-wrap">
    @foreach (['all' => __('All'), 'unread' => __('Unread').($unread ? " ($unread)" : ''), 'success' => __('Success'), 'error' => __('Errors'), 'warning' => __('Warnings'), 'info' => __('Info')] as $k => $l)
        <li class="nav-item"><a class="nav-link {{ $filter === $k ? 'active' : '' }}" href="{{ route('admin.alerts.index', ['show' => $k]) }}" data-no-modal>{{ $l }}</a></li>
    @endforeach
</ul>

<div class="card"><div class="list-group list-group-flush nx-alerts">
    @forelse ($alerts as $a)
        @php
            $icon = ['success' => 'check-circle-fill text-success', 'error' => 'exclamation-octagon-fill text-danger', 'warning' => 'exclamation-triangle-fill text-warning', 'info' => 'info-circle-fill text-info'][$a->type] ?? 'info-circle-fill';
            $src = ['order' => 'bag-check-fill', 'stock' => 'box-seam-fill'][$a->source] ?? null;
        @endphp
        <div class="list-group-item d-flex gap-3 align-items-start {{ $a->read_at ? 'opacity-75' : 'fw-semibold' }}" style="background:transparent">
            <i class="bi bi-{{ $icon }} fs-5"></i>
            <div class="flex-grow-1">
                <div>@if ($src)<i class="bi bi-{{ $src }} me-1 text-muted"></i>@endif{{ $a->message }}</div>
                <div class="small text-muted fw-normal">{{ local_time($a->created_at, 'd M Y, H:i') }} @unless ($a->read_at)<span class="badge text-bg-primary ms-1">{{ __('New') }}</span>@endunless</div>
            </div>
            <div class="d-flex gap-1">
                @if ($a->url || ! $a->read_at)<form method="POST" action="{{ route('admin.alerts.read', $a->id) }}" data-no-ajax>@csrf<button class="btn btn-sm btn-outline-secondary" title="{{ $a->url ? __('Open') : __('Mark read') }}"><i class="bi bi-{{ $a->url ? 'box-arrow-up-right' : 'check2' }}"></i></button></form>@endif
                <form method="POST" action="{{ route('admin.alerts.destroy', $a->id) }}" data-no-ajax>@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}"><i class="bi bi-x-lg"></i></button></form>
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-5"><i class="bi bi-bell-slash fs-1 d-block mb-2"></i>{{ __('No alerts here.') }}</div>
    @endforelse
</div></div>
<div class="mt-3">{{ $alerts->links() }}</div>
@endsection
