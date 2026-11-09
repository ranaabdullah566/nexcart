@extends('layouts.store')
@php $page = 'product'; @endphp
@section('title', $product->meta_title ?: $product->trans('name'))
@section('description', $product->meta_description ?: $product->trans('short_description') ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->trans('description')), 160))
@section('og_image', $product->getFirstMediaUrl('images') ?: asset('assets/images/icon-512.png'))
@section('og_type', 'product')

@section('content')
@php
    $imgs = $product->getMedia('images');
    $off = (! $product->isVariable() && $product->compare_price && $product->compare_price > $product->price) ? (int) round((1 - $product->price / $product->compare_price) * 100) : null;
    $pages = \App\Models\MenuItem::resolved('footer')->take(4);
@endphp
<div class="sf-container">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span>
        <a href="{{ route('store.shop') }}">{{ __('Shop') }}</a>
        @if ($product->category)<span>›</span><a href="{{ route('store.category', $product->category->slug) }}">{{ $product->category->trans('name') }}</a>@endif
        <span>›</span><span>{{ $product->trans('name') }}</span>
    </nav>

    <div class="mk-pd">
        <div class="mk-pd-gallery">
            <div class="sf-gallery-main mk-stage">
                @if ($imgs->isNotEmpty())<img data-main-img src="{{ $imgs->first()->getUrl() }}" alt="{{ $product->trans('name') }}">@else<i class="bi bi-box-seam ph"></i>@endif
                @if ($off)<span class="mk-disc lg">−{{ $off }}% {{ __('OFF') }}</span>@endif
                <div class="sf-tags">
                    @if (! $inStock)<span class="sf-tag out">{{ __('Out of stock') }}</span>@endif
                    @if ($product->requires_prescription)<span class="sf-tag rx"><i class="bi bi-prescription2"></i> Rx</span>@endif
                </div>
            </div>
            @if ($imgs->count() > 1)
                <div class="sf-thumbs">@foreach ($imgs as $i => $m)<button type="button" data-thumb="{{ $m->getUrl() }}" class="{{ $i === 0 ? 'on' : '' }}" aria-label="{{ $i + 1 }}"><img src="{{ $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl() }}" alt=""></button>@endforeach</div>
            @endif
            @if (sf('product.show_trust'))
            <div class="mk-pd-trust">
                @foreach (\App\Support\Sf::lines('product.trust_items') as $row)<div><i class="bi bi-{{ \App\Support\Sf::icon($row[0]) }}"></i> {{ $row[1] ?? '' }}</div>@endforeach
                @if (sf('product.show_listed'))<div><i class="bi bi-calendar-check"></i> {{ __('Listed :when', ['when' => $product->created_at?->diffForHumans()]) }}</div>@endif
            </div>
            @endif
        </div>

        <div class="mk-pd-info">
            @if ($product->category)<a class="mk-pd-cat" href="{{ route('store.category', $product->category->slug) }}"><span class="mk-mark sm"><i class="bi bi-grid-1x2"></i></span>{{ $product->category->trans('name') }}</a>@endif
            <h1>{{ $product->trans('name') }}</h1>
            <div class="mk-pd-badges">
                @if ($product->brand)<span class="mk-badge">{{ $product->brand->trans('name') }}</span>@endif
                @if ($inStock)<span class="mk-badge ok"><i class="bi bi-check-circle-fill"></i> {{ __('In stock') }}</span>@else<span class="mk-badge bad">{{ __('Out of stock') }}</span>@endif
                @if ($product->is_featured)<span class="mk-badge hot"><i class="bi bi-stars"></i> {{ __('Featured') }}</span>@endif
                @if ($rating['count'])<a href="#reviews" class="mk-badge"><span class="sf-stars" aria-hidden="true">@include('store._stars', ['value' => $rating['avg']])</span> <b class="figure">{{ $rating['avg'] }}</b> ({{ $rating['count'] }})</a>@endif
            </div>
            @if ($product->trans('short_description'))<p class="mk-pd-short">{{ $product->trans('short_description') }}</p>@endif

            <div class="mk-buybox" id="mkBuy">
                @include('store._buy')
                @php $bbTrust = \App\Support\Sf::lines('product.bb_trust'); @endphp
                @if (sf('product.show_share') || (sf('product.show_whatsapp') && filled(setting('store.whatsapp'))))
                <div class="mk-util">
                    @if (sf('product.show_share'))<button type="button" data-share data-title="{{ $product->trans('name') }}"><i class="bi bi-share"></i><span>{{ __('Share') }}</span></button>@endif
                    @if (sf('product.show_whatsapp') && filled(setting('store.whatsapp')))<a href="https://wa.me/{{ preg_replace('/\D/', '', setting('store.whatsapp')) }}?text={{ rawurlencode($product->trans('name').' '.url()->current()) }}" target="_blank" rel="noopener noreferrer" class="wa"><i class="bi bi-whatsapp"></i><span>{{ __('Ask us') }}</span></a>@endif
                </div>
                @endif
                @if ($bbTrust)<div class="mk-bb-trust">@foreach ($bbTrust as $row)<span><i class="bi bi-{{ \App\Support\Sf::icon($row[0]) }}"></i> {{ $row[1] ?? '' }}</span>@endforeach</div>@endif
            </div>

            @if (sf('product.show_meta'))
            <div class="mk-meta">
                @if ($product->sku)<div><span>SKU</span><b class="figure">{{ $product->sku }}</b></div>@endif
                <div><span>{{ __('Availability') }}</span><b>{{ $inStock ? __('In stock') : __('Out of stock') }}</b></div>
                @if ($product->track_stock && $inStock && $product->totalStock() <= 10)<div><span>{{ __('Available quantity') }}</span><b>{{ $product->totalStock() }}</b></div>@endif
                @if ($product->brand)<div><span>{{ __('Brand') }}</span><b>{{ $product->brand->trans('name') }}</b></div>@endif
                @if ($product->category)<div><span>{{ __('Category') }}</span><b>{{ $product->category->trans('name') }}</b></div>@endif
            </div>
            @endif
        </div>
    </div>

    <div class="mk-tabs" data-tabs>
        <div class="mk-tabbar" role="tablist">
            @if ($product->trans('description'))<button type="button" role="tab" class="on" data-tab="desc">{{ __('Description') }}</button>@endif
            @if ($specs->isNotEmpty())<button type="button" role="tab" data-tab="specs" class="{{ $product->trans('description') ? '' : 'on' }}">{{ __('Details') }}</button>@endif
            @if (sf('product.show_care'))<button type="button" role="tab" data-tab="care" class="{{ ! $product->trans('description') && $specs->isEmpty() ? 'on' : '' }}">{{ sft('product.care_label') }}</button>@endif
            @if (\App\Support\BusinessProfile::hasModule('reviews') && $rating['count'])<button type="button" role="tab" data-tab="reviews">{{ __('Reviews') }} ({{ $rating['count'] }})</button>@endif
        </div>
        @if ($product->trans('description'))<div class="mk-pane on" data-pane="desc"><div class="sf-prose">{!! nl2br(e($product->trans('description'))) !!}</div></div>@endif
        @if ($specs->isNotEmpty())<div class="mk-pane {{ $product->trans('description') ? '' : 'on' }}" data-pane="specs"><table class="sf-specs">@foreach ($specs as $sp)<tr><th>{{ $sp['label'] }}</th><td>{{ $sp['value'] }}</td></tr>@endforeach</table></div>@endif
        @if (sf('product.show_care'))
        <div class="mk-pane {{ ! $product->trans('description') && $specs->isEmpty() ? 'on' : '' }}" data-pane="care">
            <ul class="mk-care">
                @foreach (\App\Support\Sf::lines('product.care_items') as $row)<li><i class="bi bi-{{ \App\Support\Sf::icon($row[0]) }}"></i><div><b>{{ $row[1] ?? '' }}</b><span>{{ $row[2] ?? '' }}</span></div></li>@endforeach
            </ul>
            @if (sf('product.care_policies') && $pages->isNotEmpty())
                <p class="mk-care-title">{{ sft('product.care_policies_title') }}</p>
                <div class="mk-policy-grid">@foreach ($pages as $m)<a class="mk-policy" href="{{ $m['href'] }}"><i class="bi bi-file-earmark-text"></i><b>{{ $m['label'] }}</b><i class="bi bi-chevron-right"></i></a>@endforeach</div>
            @endif
        </div>
        @endif
        @if (\App\Support\BusinessProfile::hasModule('reviews') && $rating['count'])
            <div class="mk-pane" data-pane="reviews" id="reviews">
                <div class="d-flex align-items-center gap-2 mb-3"><span class="sf-stars fs-5" aria-hidden="true">@include('store._stars', ['value' => $rating['avg']])</span><b class="figure">{{ $rating['avg'] }}</b><span class="text-muted">{{ trans_choice(':n review|:n reviews', $rating['count'], ['n' => $rating['count']]) }}</span></div>
                @foreach ($reviews as $r)
                    <article class="sf-review">
                        <div class="d-flex justify-content-between gap-2 flex-wrap"><span class="sf-stars" aria-label="{{ $r->rating }}/5">@include('store._stars', ['value' => $r->rating])</span><small class="text-muted">{{ $r->name }} · {{ local_time($r->created_at, 'd M Y') }} · <i class="bi bi-patch-check-fill" style="color:var(--acc-1)"></i> {{ __('Verified buyer') }}</small></div>
                        @if ($r->title)<div class="fw-semibold mt-1">{{ $r->title }}</div>@endif
                        @if ($r->body)<p class="mb-0 mt-1" style="color:var(--text-2)">{{ $r->body }}</p>@endif
                        @if ($r->reply)<div class="sf-reply"><b>{{ __('Reply from :store', ['store' => setting('store.name')]) }}</b><div>{{ $r->reply }}</div></div>@endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    @if ($alternatives->isNotEmpty())
        <section class="sf-section mk-rail-sec" data-rail>
            <div class="sf-section-head"><div><h2>{{ __('Same composition, other brands') }}</h2><p class="mk-sub">{{ __('Same active ingredient (:salt) — compare prices and availability.', ['salt' => $product->specs['composition']]) }}</p></div>
                <div class="mk-rail-ctrl"><button type="button" data-rail-prev aria-label="{{ __('Previous') }}"><i class="bi bi-chevron-left"></i></button><button type="button" data-rail-next aria-label="{{ __('Next') }}"><i class="bi bi-chevron-right"></i></button></div></div>
            <div class="mk-rail" data-rail-track>@foreach ($alternatives as $p)<div class="mk-rail-item">@include('store._card', ['p' => $p])</div>@endforeach</div>
        </section>
    @endif

    @if (sf('product.show_related') && $related->isNotEmpty())
        <section class="sf-section mk-rail-sec" data-rail>
            <div class="sf-section-head"><div><h2>{{ sft('product.related_title') }}</h2><p class="mk-sub">{{ sft('product.related_sub') }}</p></div>
                <div class="mk-rail-ctrl"><button type="button" data-rail-prev aria-label="{{ __('Previous') }}"><i class="bi bi-chevron-left"></i></button><button type="button" data-rail-next aria-label="{{ __('Next') }}"><i class="bi bi-chevron-right"></i></button></div></div>
            <div class="mk-rail" data-rail-track>@foreach ($related->take(sf('product.related_count')) as $p)<div class="mk-rail-item">@include('store._card', ['p' => $p])</div>@endforeach</div>
        </section>
    @endif
</div>

{{-- phone: price + button stay in reach once the main buy box scrolls away --}}
@if (sf('product.show_sticky'))
<div class="mk-sticky-cta" data-sticky-cta hidden>
    <div><b class="figure">{{ money($product->displayPrice()) }}</b><small>{{ $inStock ? __('In stock') : __('Out of stock') }}</small></div>
    <button type="button" class="btn btn-primary" data-sticky-go>{{ $product->isVariable() ? __('Choose options') : __('Buy now') }}</button>
</div>
@endif
@endsection

@push('jsonld')
<script type="application/ld+json" nonce="{{ csp_nonce() }}">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'Product',
    'name' => $product->name, 'sku' => $product->sku, 'description' => strip_tags((string) ($product->short_description ?: $product->description)),
    'image' => $product->getMedia('images')->map->getUrl()->all() ?: null,
    'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
    'aggregateRating' => $rating['count'] ? ['@type' => 'AggregateRating', 'ratingValue' => (string) $rating['avg'], 'reviewCount' => (string) $rating['count']] : null,
    'offers' => ['@type' => 'Offer', 'url' => url()->current(), 'priceCurrency' => setting('store.currency'), 'price' => (string) $product->displayPrice(),
        'availability' => 'https://schema.org/'.($inStock ? 'InStock' : 'OutOfStock')],
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
@endpush
