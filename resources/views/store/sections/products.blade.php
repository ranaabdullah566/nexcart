@if ($products->isNotEmpty())
<section class="sf-section sf-reveal">
    <div class="sf-section-head">
        <div><span class="sf-eyebrow">{{ ['featured' => __('Featured'), 'new' => __('Just in'), 'category' => __('Collection')][$s->settings['source'] ?? 'new'] ?? '' }}</span><h2>{{ $s->text('title', __('New arrivals')) }}</h2></div>
        <a href="{{ route('store.shop') }}" class="sf-link-arrow">{{ __('View all') }} <i class="bi bi-arrow-right"></i></a>
    </div>
    @include('store._grid', ['products' => $products])
</section>
@endif
