@if ($products->isNotEmpty())
<section class="sf-section sf-reveal tq-slider" data-rail data-autoplay="5500">
    <div class="sf-section-head">
        <div><h2>{{ $s->text('title', __('New arrivals')) }}</h2><p class="mk-sub">{{ ['featured' => __('Hand-picked for you.'), 'new' => __('Fresh in the store.'), 'category' => __('A closer look.')][$s->settings['source'] ?? 'new'] ?? '' }}</p></div>
        <div class="d-flex align-items-center gap-2"><a href="{{ route('store.shop') }}" class="btn btn-outline-secondary btn-sm mk-seeall">{{ __('See all') }} →</a></div>
    </div>
    <div class="tq-slider-wrap">
        <button type="button" class="tq-nav prev" data-rail-prev aria-label="{{ __('Previous') }}"><i class="bi bi-chevron-left"></i></button>
        <div class="tq-rail tq-rail-5" data-rail-track>@foreach ($products as $p)<div class="tq-rail-item">@include('store._card', ['p' => $p])</div>@endforeach</div>
        <button type="button" class="tq-nav next" data-rail-next aria-label="{{ __('Next') }}"><i class="bi bi-chevron-right"></i></button>
    </div>
    <div class="tq-dots" data-rail-dots aria-hidden="true"></div>
</section>
@endif
