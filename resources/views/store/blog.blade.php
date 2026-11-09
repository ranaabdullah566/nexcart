@extends('layouts.store')
@php $page = 'blog'; @endphp
@section('title', __('Health blog'))
@section('description', __('Practical health tips and medicine guidance from our pharmacy team.'))

@section('content')
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><span>{{ __('Health blog') }}</span></nav>

    <header class="fx-blog-head">
        <div>
            <span class="mk-tag"><i class="bi bi-journal-richtext"></i> {{ __('Health blog') }}</span>
            <h1>{{ __('Healthy living, explained simply') }}</h1>
            <p>{{ __('Practical tips and medicine guidance from our pharmacy team.') }}</p>
        </div>
        <form method="GET" action="{{ route('store.blog') }}" class="fx-blog-search" role="search" aria-label="{{ __('Search articles…') }}">
            @if ($tag !== '')<input type="hidden" name="tag" value="{{ $tag }}">@endif
            <i class="bi bi-search"></i><input type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="{{ __('Search articles…') }}" aria-label="{{ __('Search articles…') }}"><button>{{ __('Search') }}</button>
        </form>
    </header>

    @if ($tags->isNotEmpty())
        <div class="fx-tags" aria-label="{{ __('Topics') }}">
            <a href="{{ route('store.blog', array_filter(['q' => $q])) }}" @class(['on' => $tag === ''])>{{ __('All') }}</a>
            @foreach ($tags as $t => $n)<a href="{{ route('store.blog', array_filter(['tag' => $t, 'q' => $q])) }}" @class(['on' => $tag === $t])>{{ $t }} <small>{{ $n }}</small></a>@endforeach
        </div>
    @endif

    @if ($articles->isEmpty())
        <div class="mk-empty"><span><i class="bi bi-journal-x"></i></span><h2>{{ __('No articles found') }}</h2><p>{{ __('Try another topic or search.') }}</p><a href="{{ route('store.blog') }}" class="btn btn-primary btn-lg">{{ __('See all articles') }}</a></div>
    @else
        <div class="fx-art-grid">
            @foreach ($articles as $a)
                @include('store._article_card', ['a' => $a, 'featured' => $loop->first && $articles->currentPage() === 1 && $tag === '' && $q === '', 'level' => 2])
            @endforeach
        </div>
        <div class="mt-4">{{ $articles->links('pagination::bootstrap-5') }}</div>
    @endif

    <p class="fx-disclaimer"><i class="bi bi-info-circle"></i> {{ __('This content is for general information only and is not medical advice. Always consult a doctor or pharmacist about your health.') }}</p>
</div>
@endsection
