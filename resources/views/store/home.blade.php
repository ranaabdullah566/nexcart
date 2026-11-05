@extends('layouts.store')
@section('title', setting('store.tagline') ? setting('store.name').' — '.setting('store.tagline') : '')
@php $page = 'home'; @endphp

@section('content')
@php
    $heroes = $sections->where('section.type', 'hero')->values();
    // the trust strip belongs right under the hero, whatever order the owner gave it
    $rest = $sections->reject(fn ($r) => $r['section']->type === 'hero')->sortBy(fn ($r) => $r['section']->type === 'features' ? 0 : 1)->values();
@endphp

@if ($heroes->isNotEmpty())
<section class="mk-hero" id="mkHero" data-carousel aria-roledescription="carousel" aria-label="{{ __('Highlights') }}">
    @foreach ($heroes as $row)
        @include('store.sections.hero', ['s' => $row['section'], 'first' => $loop->first, 'deals' => $deals])
    @endforeach
    @if ($heroes->count() > 1)
        <button class="mk-arrow prev" type="button" data-carousel-prev aria-label="{{ __('Previous') }}"><i class="bi bi-chevron-left"></i></button>
        <button class="mk-arrow next" type="button" data-carousel-next aria-label="{{ __('Next') }}"><i class="bi bi-chevron-right"></i></button>
        <div class="mk-dots">@foreach ($heroes as $i => $r)<button type="button" data-carousel-dot="{{ $i }}" class="{{ $i === 0 ? 'on' : '' }}" aria-label="{{ __('Slide :n', ['n' => $i + 1]) }}"></button>@endforeach</div>
    @endif
</section>
@endif

<div class="sf-container">
    @forelse ($rest as $row)
        @includeIf('store.sections.'.$row['section']->type, ['s' => $row['section']] + $row)
    @empty
        @if ($heroes->isEmpty())<div class="sf-empty"><i class="bi bi-stars"></i>{{ __('This store is being set up. Please check back soon.') }}</div>@endif
    @endforelse
</div>
@endsection
