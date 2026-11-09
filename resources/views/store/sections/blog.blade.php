@php $articles = $articles ?? collect(); @endphp
@if ($articles->isNotEmpty())
<section class="sf-section sf-reveal">
    <div class="sf-section-head"><div><h2>{{ $s->text('title', __('From our health blog')) }}</h2>@if ($s->text('subtitle'))<p class="mk-sub">{{ $s->text('subtitle') }}</p>@endif</div>
        <a href="{{ route('store.blog') }}" class="sf-link-arrow">{{ __('All articles') }} <i class="bi bi-arrow-right"></i></a></div>
    <div class="fx-art-grid fx-art-3">@foreach ($articles as $a)@include('store._article_card', ['a' => $a])@endforeach</div>
</section>
@endif
