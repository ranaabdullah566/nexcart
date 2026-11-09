@if ($categories->isNotEmpty())
<section class="sf-section sf-reveal">
    <div class="sf-section-head">
        <div><h2>{{ $s->text('title', __('Browse by category')) }}</h2><p class="mk-sub">{{ __('Find what you need in a few taps.') }}</p></div>
        <a href="{{ route('store.shop') }}" class="sf-link-arrow">{{ __('All products') }} <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="mk-tiles">
        @foreach ($categories as $c)
            <a class="mk-tile" href="{{ route('store.category', $c->slug) }}"><span class="mk-tile-mark"><i class="bi bi-{{ \App\Support\Storefront::categoryIcon($c->name) }}"></i></span><b>{{ $c->trans('name') }}</b><small>{{ trans_choice(':n item|:n items', $c->products_count, ['n' => $c->products_count]) }}</small></a>
        @endforeach
    </div>
</section>
@endif
