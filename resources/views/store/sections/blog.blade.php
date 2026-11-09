@php $articles = $articles ?? collect(); @endphp
@if ($articles->isNotEmpty())
<section class="sf-section sf-reveal" data-rail>
    <div class="sf-section-head"><div><h2>{{ $s->text('title', __('From our health blog')) }}</h2>@if ($s->text('subtitle'))<p class="mk-sub">{{ $s->text('subtitle') }}</p>@endif</div>
        <div class="d-flex align-items-center gap-3"><a href="{{ route('store.blog') }}" class="sf-link-arrow">{{ __('All articles') }} <i class="bi bi-arrow-right"></i></a>@include('store._rail-ctrl')</div></div>
    <div class="tq-rail tq-rail-3" data-rail-track>@foreach ($articles as $a)<div class="tq-rail-item">@include('store._article_card', ['a' => $a])</div>@endforeach</div>
</section>
@endif
