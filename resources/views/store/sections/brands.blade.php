@if ($brands->isNotEmpty())
<section class="sf-section sf-reveal">
    <div class="sf-section-head"><div><h2>{{ $s->text('title', __('Shop by brand')) }}</h2>@if ($s->text('subtitle'))<p class="mk-sub">{{ $s->text('subtitle') }}</p>@endif</div></div>
    <div class="mk-brands">@foreach ($brands as $b)<a href="{{ route('store.shop', ['brand' => [$b->id]]) }}" class="mk-brand-pill"><i class="bi bi-award"></i><b>{{ $b->trans('name') }}</b><small>{{ $b->products_count }}</small></a>@endforeach</div>
</section>
@endif
