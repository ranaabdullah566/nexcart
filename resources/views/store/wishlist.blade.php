@extends('layouts.store')
@php $page = 'saved'; @endphp
@section('title', __('Saved medicines'))

@section('content')
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><span>{{ __('Saved') }}</span></nav>
    <header class="tq-page-title">
        <h1><span class="tq-emoji" aria-hidden="true">❤️</span> {{ __('Saved medicines') }}</h1>
        <p>{{ __('Saved on this device. They stay here until you remove them.') }}</p>
    </header>

    @if ($products->isEmpty())
        <div class="tq-empty">
            <h2>{{ __('Nothing saved yet.') }}</h2>
            <p>{{ __('Tap the heart on any medicine to keep it here.') }}</p>
            <a href="{{ route('store.shop') }}" class="btn btn-primary btn-lg">{{ __('Browse medicines') }} <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        @if ($suggested->isNotEmpty())
            <section class="sf-section">
                <div class="sf-section-head"><div><h2>{{ __('You may like') }}</h2><p class="mk-sub">{{ __('Popular picks to start your list.') }}</p></div></div>
                @include('store._grid', ['products' => $suggested])
            </section>
        @endif
    @else
        <div class="tq-listhead"><span class="mk-count-chip">{{ trans_choice(':n item|:n items', $products->count(), ['n' => $products->count()]) }}</span>
            <a href="{{ route('store.compare') }}" class="sf-btn-ghost"><i class="bi bi-arrow-left-right"></i> {{ __('Compare') }}</a></div>
        @include('store._grid', ['products' => $products])
    @endif
</div>
@endsection
