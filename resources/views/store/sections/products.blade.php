@if ($products->isNotEmpty())
<section class="sf-section sf-reveal" data-rail>
    <div class="sf-section-head">
        <div><h2>{{ $s->text('title', __('New arrivals')) }}</h2><p class="mk-sub">{{ ['featured' => __('Hand-picked for you.'), 'new' => __('Fresh in the store.'), 'category' => __('A closer look.')][$s->settings['source'] ?? 'new'] ?? '' }}</p></div>
        <div class="d-flex align-items-center gap-2"><a href="{{ route('store.shop') }}" class="btn btn-outline-secondary btn-sm mk-seeall">{{ __('See all') }} →</a>@include('store._rail-ctrl')</div>
    </div>
    <div class="tq-rail tq-rail-6" data-rail-track>@foreach ($products as $p)<div class="tq-rail-item">@include('store._card', ['p' => $p])</div>@endforeach</div>
</section>
@endif
