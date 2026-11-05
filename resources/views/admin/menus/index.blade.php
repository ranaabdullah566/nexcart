@extends('layouts.admin')
@section('title', __('Menus'))
@section('content')
<x-page-header :title="__('Menus')" :eyebrow="__('Storefront')" :sub="__('Extra links in the header and footer. Home and Shop are always there.')" />
<div class="row g-3">
@foreach (['header' => __('Header'), 'footer' => __('Footer')] as $loc => $label)
    <div class="col-lg-6"><div class="card h-100"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">{{ $label }}</h5><a href="{{ route('admin.menu-items.create', ['location' => $loc]) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('Add link') }}</a></div>
        @forelse ($items[$loc] ?? [] as $m)
            <div class="d-flex align-items-center gap-2 py-2 border-bottom" style="border-color:var(--border) !important;{{ $m->is_active ? '' : 'opacity:.55' }}">
                <div class="flex-grow-1 min-w-0"><div class="fw-semibold text-truncate">{{ $m->label }}</div><div class="small text-muted text-truncate">{{ $m->href() ?? __('Link is not available') }}</div></div>
                <form method="POST" action="{{ route('admin.menu-items.move', $m) }}">@csrf <input type="hidden" name="dir" value="up"><button class="nx-icon-btn" style="width:32px;height:32px" @disabled($loop->first)><i class="bi bi-arrow-up"></i></button></form>
                <form method="POST" action="{{ route('admin.menu-items.move', $m) }}">@csrf <input type="hidden" name="dir" value="down"><button class="nx-icon-btn" style="width:32px;height:32px" @disabled($loop->last)><i class="bi bi-arrow-down"></i></button></form>
                <form method="POST" action="{{ route('admin.menu-items.toggle', $m) }}">@csrf <button class="nx-icon-btn" style="width:32px;height:32px" title="{{ $m->is_active ? __('Hide') : __('Show') }}"><i class="bi bi-{{ $m->is_active ? 'eye' : 'eye-slash' }}"></i></button></form>
                <x-row-actions :edit="route('admin.menu-items.edit', $m)" :delete="route('admin.menu-items.destroy', $m)" :confirm="__('Delete this link?')" />
            </div>
        @empty<div class="text-muted small py-3">{{ __('No extra links yet.') }}</div>@endforelse
    </div></div></div>
@endforeach
</div>
@endsection
