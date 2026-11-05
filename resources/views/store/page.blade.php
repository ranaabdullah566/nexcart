@extends('layouts.store')
@php $page = 'cms'; @endphp
@section('title', $cms->meta_title ?: $cms->trans('title'))
@section('description', $cms->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $cms->trans('body')), 160))

@section('content')
@php
    $others = \App\Models\Page::where('is_published', true)->whereKeyNot($cms->id)->orderBy('title')->limit(12)->get();
    $wa = filled(setting('store.whatsapp')) ? 'https://wa.me/'.preg_replace('/\D/', '', setting('store.whatsapp')) : null;
    $hasContact = filled(setting('store.email')) || filled(setting('store.phone')) || $wa;
@endphp
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><span>{{ $cms->trans('title') }}</span></nav>
    <header class="mk-cms-hero"><h1>{{ $cms->trans('title') }}</h1>@if ($cms->updated_at)<span class="mk-count-chip"><i class="bi bi-clock-history me-1"></i>{{ __('Updated :date', ['date' => local_time($cms->updated_at, 'd M Y')]) }}</span>@endif</header>

    <div class="mk-cms">
        <article class="mk-box mk-cms-body">{!! \App\Support\PageText::render($cms->trans('body')) !!}</article>

        <aside class="mk-cms-side">
            @if ($others->isNotEmpty())
                <div class="mk-box">
                    <h2 class="mk-box-title"><i class="bi bi-files"></i> {{ __('More information') }}</h2>
                    <nav class="mk-cms-links">@foreach ($others as $o)<a href="{{ route('store.page', $o->slug) }}"><i class="bi bi-file-earmark-text"></i>{{ $o->trans('title') }}<i class="bi bi-chevron-right"></i></a>@endforeach</nav>
                </div>
            @endif
            @if ($hasContact)
                <div class="mk-box mk-contact">
                    <h2 class="mk-box-title"><i class="bi bi-headset"></i> {{ __('Need help?') }}</h2>
                    <p class="mk-sub mb-3">{{ __('We are happy to answer your questions.') }}</p>
                    @if (setting('store.phone'))<a class="mk-contact-row" href="tel:{{ preg_replace('/[^0-9+]/', '', setting('store.phone')) }}" dir="ltr"><i class="bi bi-telephone"></i>{{ setting('store.phone') }}</a>@endif
                    @if (setting('store.email'))<a class="mk-contact-row" href="mailto:{{ setting('store.email') }}" dir="ltr"><i class="bi bi-envelope"></i>{{ setting('store.email') }}</a>@endif
                    @if ($wa)<a class="mk-contact-row wa" href="{{ $wa }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i>{{ __('Chat on WhatsApp') }}</a>@endif
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
