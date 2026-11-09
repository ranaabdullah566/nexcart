@extends('layouts.store')
@php
    $page = 'shop';
    $active = collect();
    foreach ($brands as $b) { if (in_array($b->id, $f['brand'] ?? [])) $active->push(['label' => $b->trans('name'), 'url' => request()->fullUrlWithQuery(['brand' => array_values(array_diff($f['brand'], [$b->id])), 'page' => null])]); }
    if (isset($f['price_min'])) $active->push(['label' => '≥ '.money($f['price_min']), 'url' => request()->fullUrlWithQuery(['price_min' => null, 'page' => null])]);
    if (isset($f['price_max'])) $active->push(['label' => '≤ '.money($f['price_max']), 'url' => request()->fullUrlWithQuery(['price_max' => null, 'page' => null])]);
    foreach (['on_sale' => __('On sale'), 'featured' => __('Featured')] as $k => $l) { if (! empty($f[$k])) $active->push(['label' => $l, 'url' => request()->fullUrlWithQuery([$k => null, 'page' => null])]); }
    foreach (array_unique($f['type'] ?? []) as $t) { $active->push(['label' => $t === 'rx' ? __('Prescription (Rx)') : __('Over the counter'), 'url' => request()->fullUrlWithQuery(['type' => array_values(array_diff($f['type'], [$t])), 'page' => null])]); }
    if (! empty($f['in_stock'])) $active->push(['label' => __('In stock'), 'url' => request()->fullUrlWithQuery(['in_stock' => null, 'page' => null])]);
    foreach ($filterAttrs as $a) { $v = $f['spec'][$a->key] ?? null; if ($v !== null && $v !== '') $active->push(['label' => $a->labelText().': '.($a->type === 'boolean' ? __('Yes') : $v), 'url' => request()->fullUrlWithQuery(['spec' => array_diff_key($f['spec'], [$a->key => 1]), 'page' => null])]); }
    $sorts = ['newest' => __('Newest'), 'price_asc' => __('Price: low to high'), 'price_desc' => __('Price: high to low'), 'name' => __('Name A–Z'), 'discount' => __('Biggest discount')];
    $sortNow = $f['sort'] ?? 'newest';
@endphp
@section('title', $current?->meta_title ?: $title)
@section('description', $current?->meta_description ?: ($current?->trans('description') ?? __('Browse all products')))

@section('content')
<div class="sf-container">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span>
        @if ($current)<a href="{{ route('store.shop') }}">{{ __('Shop') }}</a>@if ($current->parent)<span>›</span><a href="{{ route('store.category', $current->parent->slug) }}">{{ $current->parent->trans('name') }}</a>@endif<span>›</span><span>{{ $current->trans('name') }}</span>
        @else<span>{{ __('Shop') }}</span>@endif
    </nav>

    <header class="mk-shop-head">
        <div>
            <h1>{{ $title }}</h1>
            @if ($current && $current->trans('description'))<p class="mk-sub">{{ $current->trans('description') }}</p>@endif
        </div>
        <span class="mk-count-chip">{{ trans_choice(':n product|:n products', $products->total(), ['n' => $products->total()]) }}</span>
    </header>

    @if ($subcategories->isNotEmpty())
        <div class="mk-subcats">@foreach ($subcategories as $sc)<a href="{{ route('store.category', $sc->slug) }}"><i class="bi bi-tag"></i>{{ $sc->trans('name') }}</a>@endforeach</div>
    @endif

    <div class="mk-shop">
        <aside class="mk-filters">
            <button class="mk-filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#sfFilters" aria-expanded="false"><i class="bi bi-funnel"></i> {{ __('Filters') }} @if ($active->isNotEmpty())<b>{{ $active->count() }}</b>@endif</button>
            <form id="shopForm" method="GET" action="{{ url()->current() }}" class="mk-filter-card collapse d-lg-block" data-autosubmit data-no-validate>
                @if (filled($f['q'] ?? null))<input type="hidden" name="q" value="{{ $f['q'] }}">@endif
                <input type="hidden" name="sort" value="{{ $sortNow }}">
                <div class="tq-flt-search"><i class="bi bi-search"></i><input type="search" placeholder="{{ __('Search filters…') }}" aria-label="{{ __('Search filters…') }}" data-flt-search data-no-auto autocomplete="off" maxlength="40"></div>

                @php $keepQ = request()->query() ? '?'.http_build_query(request()->except(['page'])) : ''; @endphp
                <section class="tq-fsec" data-flt-sec>
                    <h3>{{ __('Category') }}</h3>
                    <ul class="tq-cattree">
                        @foreach (\App\Support\Storefront::tree() as $c1)
                            @php $open1 = $current && ($current->id === $c1->id || in_array($current->id, $c1->descendantIds())); @endphp
                            <li data-flt-item>
                                <div class="tq-catrow @if ($current && $current->id === $c1->id) on @endif">
                                    <a href="{{ route('store.category', $c1->slug) }}{{ $keepQ }}">{{ $c1->trans('name') }}</a><small>{{ $c1->products_count }}</small>
                                    @if ($c1->children->isNotEmpty())<button type="button" class="tq-exp" aria-expanded="{{ $open1 ? 'true' : 'false' }}" aria-label="{{ __('Show sub-categories') }}"><i class="bi bi-chevron-down"></i></button>@endif
                                </div>
                                @if ($c1->children->isNotEmpty())
                                <ul @unless ($open1) hidden @endunless>
                                    @foreach ($c1->children as $c2)
                                        @php $open2 = $current && ($current->id === $c2->id || in_array($current->id, $c2->descendantIds())); @endphp
                                        <li data-flt-item>
                                            <div class="tq-catrow @if ($current && $current->id === $c2->id) on @endif">
                                                <a href="{{ route('store.category', $c2->slug) }}{{ $keepQ }}">{{ $c2->trans('name') }}</a><small>{{ $c2->products_count }}</small>
                                                @if ($c2->children->isNotEmpty())<button type="button" class="tq-exp" aria-expanded="{{ $open2 ? 'true' : 'false' }}" aria-label="{{ __('Show sub-categories') }}"><i class="bi bi-chevron-down"></i></button>@endif
                                            </div>
                                            @if ($c2->children->isNotEmpty())
                                            <ul @unless ($open2) hidden @endunless>
                                                @foreach ($c2->children as $c3)
                                                    <li data-flt-item><div class="tq-catrow @if ($current && $current->id === $c3->id) on @endif"><a href="{{ route('store.category', $c3->slug) }}{{ $keepQ }}">{{ $c3->trans('name') }}</a><small>{{ $c3->products_count }}</small></div></li>
                                                @endforeach
                                            </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                @if ($brands->isNotEmpty())
                <section class="tq-fsec" data-flt-sec>
                    <h3>{{ __('Brand') }}</h3>
                    <div class="tq-fchecks">@foreach ($brands as $b)
                        <label class="tq-fcheck" data-flt-item><input type="checkbox" name="brand[]" value="{{ $b->id }}" @checked(in_array($b->id, $f['brand'] ?? []))><span>{{ $b->trans('name') }}</span><small>{{ $b->products_count }}</small></label>
                    @endforeach</div>
                </section>
                @endif

                <section class="tq-fsec" data-flt-sec>
                    <h3>{{ __('Medicine type') }}</h3>
                    <div class="tq-fchecks">
                        <label class="tq-fcheck" data-flt-item><input type="checkbox" name="type[]" value="otc" @checked(in_array('otc', $f['type'] ?? []))><span>{{ __('Over the counter') }}</span></label>
                        <label class="tq-fcheck" data-flt-item><input type="checkbox" name="type[]" value="rx" @checked(in_array('rx', $f['type'] ?? []))><span><i class="bi bi-prescription2"></i> {{ __('Prescription (Rx)') }}</span></label>
                    </div>
                </section>

                <section class="tq-fsec" data-flt-sec>
                    <h3>{{ __('Availability & offers') }}</h3>
                    <div class="tq-fchecks">
                        <label class="tq-fcheck" data-flt-item><input type="checkbox" name="in_stock" value="1" @checked(! empty($f['in_stock']))><span>{{ __('In stock only') }}</span></label>
                        <label class="tq-fcheck" data-flt-item><input type="checkbox" name="on_sale" value="1" @checked(! empty($f['on_sale']))><span>{{ __('On sale') }}</span></label>
                        <label class="tq-fcheck" data-flt-item><input type="checkbox" name="featured" value="1" @checked(! empty($f['featured']))><span>{{ __('Featured') }}</span></label>
                    </div>
                </section>

                @foreach ($filterAttrs as $a)
                    <section class="tq-fsec" data-flt-sec>
                        <h3>{{ $a->labelText() }}</h3>
                        <select name="spec[{{ $a->key }}]" class="form-select form-select-sm">
                            <option value="">{{ __('Any') }}</option>
                            @if ($a->type === 'boolean')<option value="1" @selected(($f['spec'][$a->key] ?? '') === '1')>{{ __('Yes') }}</option>
                            @else @foreach ($a->options ?? [] as $o)<option @selected(($f['spec'][$a->key] ?? '') === $o)>{{ $o }}</option>@endforeach @endif
                        </select>
                    </section>
                @endforeach

                <section class="tq-fsec">
                    <h3>{{ __('Price') }} ({{ setting('store.currency_symbol', 'Rs') }})</h3>
                    <div class="tq-fprice">
                        <input type="number" min="0" step="any" name="price_min" value="{{ $f['price_min'] ?? '' }}" placeholder="{{ __('Min') }}" class="form-control form-control-sm" aria-label="{{ __('Min price') }}">
                        <input type="number" min="0" step="any" name="price_max" value="{{ $f['price_max'] ?? '' }}" placeholder="{{ __('Max') }}" class="form-control form-control-sm" aria-label="{{ __('Max price') }}">
                    </div>
                    <p class="tq-fhint">{{ money($priceBounds['min']) }} – {{ money($priceBounds['max']) }}</p>
                </section>

                <a class="tq-fclear" href="{{ url()->current() }}{{ filled($f['q'] ?? null) ? '?q='.urlencode($f['q']) : '' }}">{{ __('Clear all') }}</a>
                <noscript><button class="btn btn-primary btn-sm mt-3 w-100">{{ __('Apply') }}</button></noscript>
            </form>
        </aside>

        <div class="mk-shop-main">
            <div class="mk-toolbar">
                <div class="mk-chips">
                    @foreach ($active as $chip)<a class="mk-chip" href="{{ $chip['url'] }}">{{ $chip['label'] }} <i class="bi bi-x"></i></a>@endforeach
                    @if (filled($f['q'] ?? null))<span class="mk-chip static"><i class="bi bi-search"></i> “{{ $f['q'] }}”</span>@endif
                </div>
                <div class="mk-toolbar-right">
                    <label class="mk-sort"><span class="d-none d-sm-inline">{{ __('Sort by') }}</span>
                        <select class="form-select form-select-sm" aria-label="{{ __('Sort by') }}" data-sort-select data-no-validate>
                            @foreach ($sorts as $k => $l)<option value="{{ request()->fullUrlWithQuery(['sort' => $k, 'page' => null]) }}" @selected($sortNow === $k)>{{ $l }}</option>@endforeach
                        </select></label>
                    <div class="mk-view" data-view-toggle role="group" aria-label="{{ __('View') }}">
                        <button type="button" data-view="grid" class="on" aria-pressed="true" aria-label="{{ __('Grid') }}"><i class="bi bi-grid-3x3-gap"></i></button>
                        <button type="button" data-view="list" aria-pressed="false" aria-label="{{ __('List') }}"><i class="bi bi-list-ul"></i></button>
                    </div>
                </div>
            </div>

            @if ($products->isEmpty())
                <div class="mk-empty"><span><i class="bi bi-search"></i></span><h2>{{ __('No products found') }}</h2><p>{{ __('Try removing a filter or searching for something else.') }}</p>@if ($active->isNotEmpty() || filled($f['q'] ?? null))<a href="{{ route('store.shop') }}" class="btn btn-primary">{{ __('Clear filters') }}</a>@endif</div>
            @else
                <div data-product-list>
                    @include('store._grid', ['products' => $products, 'cols' => 'row-cols-xl-'.max(2, (int) sf('cards.cols') - 1)])
                </div>
                <div class="mk-pager">
                    <small>{{ __('Showing :from–:to of :total', ['from' => $products->firstItem(), 'to' => $products->lastItem(), 'total' => $products->total()]) }}</small>
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
    document.querySelectorAll('[data-sort-select]').forEach(function (s) { s.addEventListener('change', function () { location.href = s.value; }); });
</script>
@endpush
