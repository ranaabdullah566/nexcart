@if ($products->isNotEmpty())
<section class="sf-section sf-reveal">
    <div class="sf-section-head">
        <div><h2>{{ $s->text('title', __('New arrivals')) }}</h2><p class="mk-sub">{{ ['featured' => __('Hand-picked for you.'), 'new' => __('Fresh in the store.'), 'category' => __('A closer look.')][$s->settings['source'] ?? 'new'] ?? '' }}</p></div>
        <a href="{{ route('store.shop') }}" class="btn btn-outline-secondary btn-sm mk-seeall">{{ __('See all') }} →</a>
    </div>
    @include('store._grid', ['products' => $products])
</section>
@endif
