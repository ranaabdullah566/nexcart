@extends('layouts.store')
@php
    $page = 'article';
    $title = $article->trans('title');
    $cover = $article->coverUrl('wide');
    $url = route('store.article', $article->slug);
    $icon = \App\Support\Storefront::categoryIcon($article->tag ?: $article->title);
    $hue = crc32((string) $article->slug) % 360;
@endphp
@section('title', $article->meta_title ?: $title)
@section('description', $article->meta_description ?: $article->summary(160))
@section('og_type', 'article')
@if ($cover)@section('og_image', $cover)@endif

@section('content')
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><a href="{{ route('store.blog') }}">{{ __('Health blog') }}</a><span>›</span><span>{{ \Illuminate\Support\Str::limit($title, 50) }}</span></nav>

    <article class="fx-article" itemscope itemtype="https://schema.org/Article">
        <header class="fx-article-head">
            @if ($article->tag)<a href="{{ route('store.blog', ['tag' => $article->tag]) }}" class="fx-art-tag static">{{ $article->trans('tag') }}</a>@endif
            <h1 itemprop="headline" dir="auto">{{ $title }}</h1>
            <div class="fx-art-meta"><span><i class="bi bi-person-circle"></i> {{ $article->author ?: setting('store.name') }}</span><span>·</span><time itemprop="datePublished" datetime="{{ $article->date()->toIso8601String() }}">{{ $article->date()->translatedFormat('d M Y') }}</time><span>·</span><span><i class="bi bi-clock"></i> {{ __(':n min read', ['n' => $article->readingMinutes()]) }}</span></div>
        </header>
        <div class="fx-article-cover" style="--h: {{ $hue }}">@if ($cover)<img src="{{ $cover }}" alt="" itemprop="image">@else<span class="fx-art-ph"><i class="bi bi-{{ $icon }}"></i></span>@endif</div>

        <div class="fx-article-layout">
            <div class="mk-box mk-cms-body fx-article-body" itemprop="articleBody" dir="auto">{!! \App\Support\PageText::render($article->trans('body')) !!}</div>
            <aside class="fx-article-side">
                <div class="mk-box fx-share">
                    <h2 class="mk-box-title"><i class="bi bi-share"></i> {{ __('Share') }}</h2>
                    <div class="fx-share-btns">
                        <a href="https://wa.me/?text={{ rawurlencode($title.' '.$url) }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($url) }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="https://twitter.com/intent/tweet?text={{ rawurlencode($title) }}&url={{ rawurlencode($url) }}" target="_blank" rel="noopener noreferrer" aria-label="X"><i class="bi bi-twitter-x"></i></a>
                        <button type="button" data-share data-title="{{ $title }}" aria-label="{{ __('Share') }}"><i class="bi bi-link-45deg"></i></button>
                    </div>
                </div>
                <div class="mk-box fx-shop-cta">
                    <span class="fx-modal-ic"><i class="bi bi-capsule"></i></span>
                    <h2>{{ __('Need medicines?') }}</h2>
                    <p>{{ __('Order genuine medicines online, or send us your prescription.') }}</p>
                    <a href="{{ route('store.shop') }}" class="btn btn-primary w-100 mb-2"><i class="bi bi-bag me-1"></i> {{ __('Shop medicines') }}</a>
                    @if (\App\Support\BusinessProfile::hasModule('prescriptions'))<a href="{{ route('store.prescription.upload') }}" class="btn btn-outline-primary w-100"><i class="bi bi-camera me-1"></i> {{ __('Upload prescription') }}</a>@endif
                </div>
            </aside>
        </div>
    </article>

    <p class="fx-disclaimer"><i class="bi bi-info-circle"></i> {{ __('This content is for general information only and is not medical advice. Always consult a doctor or pharmacist about your health.') }}</p>

    @if ($more->isNotEmpty())
        <section class="sf-section">
            <div class="sf-section-head"><div><h2>{{ __('More to read') }}</h2></div><a href="{{ route('store.blog') }}" class="sf-link-arrow">{{ __('All articles') }} <i class="bi bi-arrow-right"></i></a></div>
            <div class="fx-art-grid fx-art-3">@foreach ($more as $a)@include('store._article_card', ['a' => $a])@endforeach</div>
        </section>
    @endif
</div>
@endsection
