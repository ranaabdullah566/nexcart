@if ($categories->isNotEmpty())
<section class="sf-section sf-reveal">
    <div class="sf-section-head">
        <div><span class="sf-eyebrow">{{ __('Browse') }}</span><h2>{{ $s->text('title', __('Shop by category')) }}</h2></div>
        <a href="{{ route('store.shop') }}" class="sf-link-arrow">{{ __('All products') }} <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-lg-4">
        @foreach ($categories as $c)
            <div class="col"><a class="sf-cat" href="{{ route('store.category', $c->slug) }}"><i class="bi bi-grid-1x2"></i><b>{{ $c->trans('name') }}</b><small>{{ trans_choice(':n item|:n items', $c->products_count, ['n' => $c->products_count]) }}</small></a></div>
        @endforeach
    </div>
</section>
@endif
