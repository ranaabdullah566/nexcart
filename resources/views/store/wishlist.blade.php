@extends('layouts.store')
@php $page = 'wishlist'; @endphp
@section('title', __('Wishlist'))

@section('content')
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><span>{{ __('Wishlist') }}</span></nav>
    <div class="mk-page-head">
        <h1><i class="bi bi-heart-fill mk-heart"></i> {{ __('Wishlist') }}</h1>
        @if ($products->isNotEmpty())
            <div class="d-flex gap-2 flex-wrap align-items-center"><span class="mk-count-chip">{{ trans_choice(':n item|:n items', $products->count(), ['n' => $products->count()]) }}</span>
                <a href="{{ route('store.compare') }}" class="sf-btn-ghost"><i class="bi bi-arrow-left-right"></i> {{ __('Compare') }}</a></div>
        @endif
    </div>

    @if ($products->isEmpty())
        <div class="mk-empty"><span><i class="bi bi-heart"></i></span><h2>{{ __('Nothing saved yet') }}</h2><p>{{ __('Tap the heart on any product to keep it here for later.') }}</p><a href="{{ route('store.shop') }}" class="btn btn-primary btn-lg">{{ __('Browse products') }}</a></div>
        @if ($suggested->isNotEmpty())
            <section class="sf-section">
                <div class="sf-section-head"><div><h2>{{ __('You may like') }}</h2><p class="mk-sub">{{ __('Popular picks to start your list.') }}</p></div></div>
                @include('store._grid', ['products' => $suggested])
            </section>
        @endif
    @else
        @include('store._grid', ['products' => $products])
    @endif
</div>
@endsection
