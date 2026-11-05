@extends('layouts.store')
@php
    $page = 'shop';
    $active = collect();
    foreach ($brands as $b) { if (in_array($b->id, $f['brand'] ?? [])) $active->push(['label' => $b->trans('name'), 'url' => request()->fullUrlWithQuery(['brand' => array_values(array_diff($f['brand'], [$b->id])), 'page' => null])]); }
    if (isset($f['price_min'])) $active->push(['label' => '≥ '.money($f['price_min']), 'url' => request()->fullUrlWithQuery(['price_min' => null, 'page' => null])]);
    if (isset($f['price_max'])) $active->push(['label' => '≤ '.money($f['price_max']), 'url' => request()->fullUrlWithQuery(['price_max' => null, 'page' => null])]);
    if (! empty($f['in_stock'])) $active->push(['label' => __('In stock'), 'url' => request()->fullUrlWithQuery(['in_stock' => null, 'page' => null])]);
    foreach ($filterAttrs as $a) { $v = $f['spec'][$a->key] ?? null; if ($v !== null && $v !== '') $active->push(['label' => $a->labelText().': '.($a->type === 'boolean' ? __('Yes') : $v), 'url' => request()->fullUrlWithQuery(['spec' => array_diff_key($f['spec'], [$a->key => 1]), 'page' => null])]); }
@endphp
@section('title', $title)
@section('description', $current?->trans('description') ?? __('Browse all products'))

@section('content')
<div class="sf-container">
    <nav class="sf-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>/</span>
        @if ($current)<a href="{{ route('store.shop') }}">{{ __('Shop') }}</a>@if ($current->parent)<span>/</span><a href="{{ route('store.category', $current->parent->slug) }}">{{ $current->parent->trans('name') }}</a>@endif<span>/</span><span>{{ $current->trans('name') }}</span>
        @else<span>{{ __('Shop') }}</span>@endif
    </nav>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
        <div><h1 class="h2 mb-1">{{ $title }}</h1><div class="text-muted small">{{ trans_choice(':n product|:n products', $products->total(), ['n' => $products->total()]) }}</div></div>
        <button class="sf-btn-ghost d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#sfFilters"><i class="bi bi-funnel"></i> {{ __('Filters') }}</button>
    </div>

    @if ($subcategories->isNotEmpty())
        <div class="d-flex flex-wrap gap-2 mb-3">@foreach ($subcategories as $sc)<a class="sf-chip" style="color:var(--text)" href="{{ route('store.category', $sc->slug) }}">{{ $sc->trans('name') }}</a>@endforeach</div>
    @endif

    <div class="row g-4">
        <aside class="col-lg-3">
            <form id="shopForm" method="GET" action="{{ url()->current() }}" class="sf-filter collapse d-lg-block" data-autosubmit>
                @if (filled($f['q'] ?? null))<input type="hidden" name="q" value="{{ $f['q'] }}">@endif
                <h6>{{ __('Sort by') }}</h6>
                <select name="sort" class="form-select form-select-sm">
                    @foreach (['newest' => __('Newest'), 'price_asc' => __('Price: low to high'), 'price_desc' => __('Price: high to low'), 'name' => __('Name A–Z'), 'discount' => __('Biggest discount')] as $k => $l)<option value="{{ $k }}" @selected(($f['sort'] ?? 'newest') === $k)>{{ $l }}</option>@endforeach
                </select>

                @if ($brands->isNotEmpty())
                    <h6>{{ __('Brand') }}</h6>
                    @foreach ($brands as $b)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="brand[]" value="{{ $b->id }}" id="b{{ $b->id }}" @checked(in_array($b->id, $f['brand'] ?? []))><label class="form-check-label" for="b{{ $b->id }}">{{ $b->trans('name') }}</label></div>
                    @endforeach
                @endif

                <h6>{{ __('Price') }}</h6>
                <div class="d-flex gap-2 align-items-center">
                    <input type="number" min="0" step="any" name="price_min" value="{{ $f['price_min'] ?? '' }}" placeholder="{{ floor($priceBounds['min']) }}" class="form-control form-control-sm figure" aria-label="{{ __('Min price') }}">
                    <span>–</span>
                    <input type="number" min="0" step="any" name="price_max" value="{{ $f['price_max'] ?? '' }}" placeholder="{{ ceil($priceBounds['max']) }}" class="form-control form-control-sm figure" aria-label="{{ __('Max price') }}">
                </div>

                @foreach ($filterAttrs as $a)
                    <h6>{{ $a->labelText() }}</h6>
                    <select name="spec[{{ $a->key }}]" class="form-select form-select-sm">
                        <option value="">{{ __('Any') }}</option>
                        @if ($a->type === 'boolean')<option value="1" @selected(($f['spec'][$a->key] ?? '') === '1')>{{ __('Yes') }}</option>
                        @else @foreach ($a->options ?? [] as $o)<option @selected(($f['spec'][$a->key] ?? '') === $o)>{{ $o }}</option>@endforeach @endif
                    </select>
                @endforeach

                <div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" role="switch" name="in_stock" value="1" id="inStock" @checked(! empty($f['in_stock']))><label class="form-check-label" for="inStock">{{ __('In stock only') }}</label></div>

                <noscript><button class="btn btn-primary btn-sm mt-3 w-100">{{ __('Apply') }}</button></noscript>
                @if ($active->isNotEmpty())<a href="{{ url()->current() }}{{ filled($f['q'] ?? null) ? '?q='.urlencode($f['q']) : '' }}" class="btn btn-outline-secondary btn-sm w-100 mt-3">{{ __('Clear filters') }}</a>@endif
            </form>
        </aside>

        <div class="col-lg-9">
            @if ($active->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mb-3">@foreach ($active as $chip)<a class="sf-chip" href="{{ $chip['url'] }}">{{ $chip['label'] }} <i class="bi bi-x"></i></a>@endforeach</div>
            @endif

            @if ($products->isEmpty())
                <div class="sf-empty"><i class="bi bi-search"></i><h2 class="h5">{{ __('No products found') }}</h2><p>{{ __('Try removing a filter or searching for something else.') }}</p></div>
            @else
                @include('store._grid', ['products' => $products, 'cols' => 'row-cols-xl-3'])
                <div class="mt-4 d-flex justify-content-center">{{ $products->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
