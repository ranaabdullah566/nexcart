@extends('layouts.admin')
@section('title', __('Home page'))

@php
    $meta = [
        'hero' => ['window-fullscreen', 'Hero banner'], 'categories' => ['grid-1x2', 'Category tiles'], 'products' => ['box-seam', 'Product row'],
        'banner' => ['image', 'Promo banner'], 'features' => ['stars', 'Highlights'], 'text' => ['card-text', 'Text block'],
        'brands' => ['award', 'Brands strip'], 'cta' => ['megaphone', 'Call-to-action banner'], 'steps' => ['list-ol', 'How it works (steps)'], 'testimonials' => ['chat-quote', 'Customer quotes'], 'faq' => ['question-circle', 'Questions & answers'],
    ];
@endphp

@section('content')
<x-page-header :title="__('Home page')" :eyebrow="__('Storefront')" :sub="__('Build what shoppers see first. Reorder, hide or edit any section.')">
    <x-slot:actions>
        <a href="{{ route('store.home') }}" target="_blank" class="btn btn-outline-secondary" data-no-modal><i class="bi bi-box-arrow-up-right me-1"></i> {{ __('View store') }}</a>
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-plus-lg me-1"></i> {{ __('Add section') }}</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach ($types as $t)<li><a class="dropdown-item" href="{{ route('admin.home-sections.create', ['type' => $t]) }}"><i class="bi bi-{{ $meta[$t][0] }} me-2"></i>{{ __($meta[$t][1]) }}</a></li>@endforeach
            </ul>
        </div>
    </x-slot:actions>
</x-page-header>

<div class="d-grid gap-2">
    @forelse ($sections as $s)
        <div class="card" style="{{ $s->is_active ? '' : 'opacity:.6' }}"><div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <span class="nx-chip-avatar"><i class="bi bi-{{ $meta[$s->type][0] }}"></i></span>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-truncate">{{ $s->title ?: __($meta[$s->type][1]) }}</div>
                <div class="small text-muted">{{ __($meta[$s->type][1]) }} @unless ($s->is_active)· <span class="text-warning">{{ __('Hidden') }}</span>@endunless</div>
            </div>
            <div class="d-flex gap-1 align-items-center">
                <form method="POST" action="{{ route('admin.home-sections.move', $s) }}">@csrf <input type="hidden" name="dir" value="up"><button class="nx-icon-btn" style="width:34px;height:34px" title="{{ __('Move up') }}" @disabled($loop->first)><i class="bi bi-arrow-up"></i></button></form>
                <form method="POST" action="{{ route('admin.home-sections.move', $s) }}">@csrf <input type="hidden" name="dir" value="down"><button class="nx-icon-btn" style="width:34px;height:34px" title="{{ __('Move down') }}" @disabled($loop->last)><i class="bi bi-arrow-down"></i></button></form>
                <form method="POST" action="{{ route('admin.home-sections.toggle', $s) }}">@csrf <button class="nx-icon-btn" style="width:34px;height:34px" title="{{ $s->is_active ? __('Hide') : __('Show') }}"><i class="bi bi-{{ $s->is_active ? 'eye' : 'eye-slash' }}"></i></button></form>
                <x-row-actions :edit="route('admin.home-sections.edit', $s)" :delete="route('admin.home-sections.destroy', $s)" :confirm="__('Delete this section?')" />
            </div>
        </div></div>
    @empty
        <div class="card"><div class="card-body text-center text-muted py-5">{{ __('No sections yet. Add one to start building your home page.') }}</div></div>
    @endforelse
</div>
@endsection
