@extends('layouts.store')
@php($page = 'product')
@section('title', $product->trans('name'))
@section('description', $product->trans('short_description') ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->trans('description')), 160))
@section('og_image', $product->getFirstMediaUrl('images') ?: asset('assets/images/icon-512.png'))
@section('og_type', 'product')

@section('content')
<div class="sf-container">
    <nav class="sf-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>/</span>
        <a href="{{ route('store.shop') }}">{{ __('Shop') }}</a>
        @if ($product->category)<span>/</span><a href="{{ route('store.category', $product->category->slug) }}">{{ $product->category->trans('name') }}</a>@endif
        <span>/</span><span>{{ $product->trans('name') }}</span>
    </nav>

    <div class="row g-4 g-lg-5 mt-1">
        <div class="col-lg-6">
            @php($imgs = $product->getMedia('images'))
            <div class="sf-gallery-main">
                @if ($imgs->isNotEmpty())<img data-main-img src="{{ $imgs->first()->getUrl() }}" alt="{{ $product->trans('name') }}">@else<i class="bi bi-box-seam ph"></i>@endif
                <div class="sf-tags">
                    @if (! $inStock)<span class="sf-tag out">{{ __('Out of stock') }}</span>@endif
                    @if ($product->requires_prescription)<span class="sf-tag rx"><i class="bi bi-prescription2"></i> Rx</span>@endif
                </div>
            </div>
            @if ($imgs->count() > 1)
                <div class="sf-thumbs">@foreach ($imgs as $i => $m)<button type="button" data-thumb="{{ $m->getUrl() }}" class="{{ $i === 0 ? 'on' : '' }}" aria-label="{{ $i + 1 }}"><img src="{{ $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl() }}" alt=""></button>@endforeach</div>
            @endif
        </div>
        <div class="col-lg-6">
            <div class="sf-buy">
                <div class="d-flex gap-2 flex-wrap mb-2">
                    @if ($product->brand)<span class="sf-chip">{{ $product->brand->trans('name') }}</span>@endif
                    @if ($product->sku)<span class="sf-chip figure">SKU {{ $product->sku }}</span>@endif
                </div>
                <h1>{{ $product->trans('name') }}</h1>
                @if ($product->trans('short_description'))<p class="text-muted fs-5">{{ $product->trans('short_description') }}</p>@endif
                @include('store._buy')
            </div>
        </div>
    </div>

    <div class="row g-4 mt-4">
        <div class="col-lg-7">
            @if ($product->trans('description'))
                <h3 class="h5 mb-3">{{ __('Description') }}</h3>
                <div class="sf-prose">{!! nl2br(e($product->trans('description'))) !!}</div>
            @endif
        </div>
        <div class="col-lg-5">
            @if ($specs->isNotEmpty())
                <h3 class="h5 mb-3">{{ __('Details') }}</h3>
                <table class="sf-specs">@foreach ($specs as $sp)<tr><th>{{ $sp['label'] }}</th><td>{{ $sp['value'] }}</td></tr>@endforeach</table>
            @endif
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="sf-section">
            <div class="sf-section-head"><div><span class="sf-eyebrow">{{ __('You may also like') }}</span><h2>{{ __('Related products') }}</h2></div></div>
            @include('store._grid', ['products' => $related])
        </section>
    @endif
</div>
@endsection

@push('jsonld')
<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org', '@type' => 'Product',
    'name' => $product->name, 'sku' => $product->sku, 'description' => strip_tags((string) ($product->short_description ?: $product->description)),
    'image' => $product->getMedia('images')->map->getUrl()->all() ?: null,
    'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
    'offers' => ['@type' => 'Offer', 'url' => url()->current(), 'priceCurrency' => setting('store.currency'), 'price' => (string) $product->displayPrice(),
        'availability' => 'https://schema.org/'.($inStock ? 'InStock' : 'OutOfStock')],
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
</script>
@endpush
