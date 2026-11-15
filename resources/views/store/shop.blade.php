@extends('layouts.store')
@php
    $page = 'shop';
    $active = collect();
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

    @php
        $keepQ = request()->query() ? '?'.http_build_query(request()->except(['page'])) : '';
        $chipCats = $current ? ($subcategories->isNotEmpty() ? $subcategories : ($current->parent ? $current->parent->children()->where('is_active', true)->get() : collect())) : \App\Support\Storefront::tree();
        $chipUp = $current ? ($subcategories->isNotEmpty() ? $current : ($current->parent ?? $current)) : null;
        $keepForSearch = collect(request()->except(['q', 'page']))->filter(fn ($v) => is_scalar($v) || is_array($v));
        $from = $products->firstItem() ?? 0; $to = $products->lastItem() ?? 0; $total = $products->total();
    @endphp
    <header class="sh-hero">
        <div class="sh-hero-t">
            <h1>{{ $title }}</h1>
            @if ($current && $current->trans('description'))<p class="mk-sub">{{ $current->trans('description') }}</p>@else<p class="mk-sub">{{ __('Genuine medicines and health products, delivered safely.') }}</p>@endif
        </div>
        <form class="sh-search" role="search" method="GET" action="{{ url()->current() }}" data-no-validate>
            @foreach ($keepForSearch as $k => $v)@if (is_array($v))@foreach ($v as $vv)@if (is_scalar($vv))<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endif @endforeach @elseif ($k !== 'sort' || filled($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
            <i class="bi bi-search" aria-hidden="true"></i>
            <label class="visually-hidden" for="shQ">{{ __('Search in this list') }}</label>
            <input id="shQ" type="search" name="q" value="{{ $f['q'] ?? '' }}" maxlength="100" placeholder="{{ __('Search medicines, salts…') }}" autocomplete="off">
            <button type="submit">{{ __('Search') }}</button>
        </form>
    </header>

    @if ($chipCats->isNotEmpty())
        <nav class="sh-tiles" aria-label="{{ __('Categories') }}">
            <a href="{{ $chipUp ? route('store.category', $chipUp->slug).$keepQ : route('store.shop').$keepQ }}" @class(['on' => ! $current || ($chipUp && $current->id === $chipUp->id)])>
                <span class="sh-tile-img all"><i class="bi bi-grid-3x3-gap-fill"></i></span><b>{{ $chipUp ? __('All') : __('All') }}</b></a>
            @foreach ($chipCats as $sc)@php $im = $sc->imageUrl(); @endphp
                <a href="{{ route('store.category', $sc->slug) }}{{ $keepQ }}" @class(['on' => $current && $current->id === $sc->id])>
                    <span class="sh-tile-img">@if ($im)<img src="{{ $im }}" alt="" loading="lazy">@else<i class="bi bi-{{ \App\Support\Storefront::categoryIcon($sc->name) }}"></i>@endif</span><b>{{ $sc->trans('name') }}</b></a>
            @endforeach
        </nav>
    @endif

    <div class="mk-shop">
        <aside class="mk-filters offcanvas-lg offcanvas-start" tabindex="-1" id="sfFilters" aria-labelledby="sfFiltersT">
            <div class="sh-fhead d-lg-none"><b id="sfFiltersT"><i class="bi bi-funnel"></i> {{ __('Filters') }}</b><button type="button" class="sh-x" data-bs-dismiss="offcanvas" data-bs-target="#sfFilters" aria-label="{{ __('Close') }}"><i class="bi bi-x-lg"></i></button></div>
            <div class="sh-fbody">
            <form id="shopForm" method="GET" action="{{ url()->current() }}" class="mk-filter-card" data-autosubmit data-no-validate>
                @if (filled($f['q'] ?? null))<input type="hidden" name="q" value="{{ $f['q'] }}">@endif
                <input type="hidden" name="sort" value="{{ $sortNow }}">
                <div class="tq-flt-search"><i class="bi bi-search"></i><input type="search" placeholder="{{ __('Search filters…') }}" aria-label="{{ __('Search filters…') }}" data-flt-search data-no-auto autocomplete="off" maxlength="40"></div>

                                {{-- ===== categories: picture, sub-categories and third level ===== --}}
                <section class="tq-fsec sx-cats" data-flt-sec>
                    <h3>{{ __('Shop by category') }}</h3>
                    <ul class="sx-tree">
                        @foreach (\App\Support\Storefront::tree() as $c1)
                            @php $open1 = $current && ($current->id === $c1->id || in_array($current->id, $c1->descendantIds())); $img1 = $c1->imageUrl(); $n1 = $c1->products_count + $c1->children->sum(fn ($x) => $x->products_count + $x->children->sum('products_count')); @endphp
                            <li class="sx-c1 @if ($open1) is-open @endif" data-flt-item>
                                <div class="sx-row @if ($current && $current->id === $c1->id) on @endif">
                                    <a class="sx-link" href="{{ route('store.category', $c1->slug) }}{{ $keepQ }}">
                                        <span class="sx-img">@if ($img1)<img src="{{ $img1 }}" alt="" loading="lazy">@else<i class="bi bi-{{ \App\Support\Storefront::categoryIcon($c1->name) }}"></i>@endif</span>
                                        <span class="sx-name">{{ $c1->trans('name') }}</span><small>{{ $n1 }}</small>
                                    </a>
                                    @if ($c1->children->isNotEmpty())<button type="button" class="tq-exp sx-exp" aria-expanded="{{ $open1 ? 'true' : 'false' }}" aria-label="{{ __('Show sub-categories') }}"><i class="bi bi-chevron-down"></i></button>@endif
                                </div>
                                @if ($c1->children->isNotEmpty())
                                <ul class="sx-sub" @unless ($open1) hidden @endunless>
                                    @foreach ($c1->children as $c2)
                                        @php $open2 = $current && ($current->id === $c2->id || in_array($current->id, $c2->descendantIds())); $img2 = $c2->imageUrl(); $n2 = $c2->products_count + $c2->children->sum('products_count'); @endphp
                                        <li data-flt-item>
                                            <div class="sx-row sx-row2 @if ($current && $current->id === $c2->id) on @endif">
                                                <a class="sx-link" href="{{ route('store.category', $c2->slug) }}{{ $keepQ }}">
                                                    <span class="sx-img sm">@if ($img2)<img src="{{ $img2 }}" alt="" loading="lazy">@else<i class="bi bi-{{ \App\Support\Storefront::categoryIcon($c2->name) }}"></i>@endif</span>
                                                    <span class="sx-name">{{ $c2->trans('name') }}</span><small>{{ $n2 }}</small>
                                                </a>
                                                @if ($c2->children->isNotEmpty())<button type="button" class="tq-exp sx-exp" aria-expanded="{{ $open2 ? 'true' : 'false' }}" aria-label="{{ __('Show sub-categories') }}"><i class="bi bi-chevron-down"></i></button>@endif
                                            </div>
                                            @if ($c2->children->isNotEmpty())
                                            <div class="sx-chips" @unless ($open2) hidden @endunless>
                                                @foreach ($c2->children as $c3)<a data-flt-item href="{{ route('store.category', $c3->slug) }}{{ $keepQ }}" @class(['on' => $current && $current->id === $c3->id])>{{ $c3->trans('name') }} <small>{{ $c3->products_count }}</small></a>@endforeach
                                            </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="tq-fsec" data-flt-sec>
                    <h3>{{ __('Medicine type') }}</h3>
                    <div class="sx-seg" role="group">
                        <label><input type="checkbox" name="type[]" value="otc" @checked(in_array('otc', $f['type'] ?? []))><span><i class="bi bi-bag-check"></i> {{ __('Over the counter') }}</span></label>
                        <label><input type="checkbox" name="type[]" value="rx" @checked(in_array('rx', $f['type'] ?? []))><span><i class="bi bi-prescription2"></i> {{ __('Prescription (Rx)') }}</span></label>
                    </div>
                </section>

                <section class="tq-fsec" data-flt-sec>
                    <h3>{{ __('Availability & offers') }}</h3>
                    <div class="sx-switches">
                        <label class="sx-sw"><span><i class="bi bi-box-seam"></i> {{ __('In stock only') }}</span><input type="checkbox" role="switch" name="in_stock" value="1" @checked(! empty($f['in_stock']))><i class="sx-knob"></i></label>
                        <label class="sx-sw"><span><i class="bi bi-percent"></i> {{ __('On sale') }}</span><input type="checkbox" role="switch" name="on_sale" value="1" @checked(! empty($f['on_sale']))><i class="sx-knob"></i></label>
                        <label class="sx-sw"><span><i class="bi bi-stars"></i> {{ __('Featured') }}</span><input type="checkbox" role="switch" name="featured" value="1" @checked(! empty($f['featured']))><i class="sx-knob"></i></label>
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

                @php
                    $pmin = (int) floor($priceBounds['min']); $pmax = (int) ceil($priceBounds['max']); $pmax = max($pmax, $pmin + 1);
                    $vmin = (int) ($f['price_min'] ?? $pmin); $vmax = (int) ($f['price_max'] ?? $pmax);
                    $step = max(1, (int) round(($pmax - $pmin) / 100));
                    $quick = collect([[0, 500], [500, 1000], [1000, 3000], [3000, null]])->filter(fn ($q) => $q[0] < $pmax && ($q[1] === null || $q[1] > $pmin));
                @endphp
                <section class="tq-fsec sx-price" data-sx-price data-min="{{ $pmin }}" data-max="{{ $pmax }}">
                    <h3>{{ __('Price') }} ({{ setting('store.currency_symbol', 'Rs') }})</h3>
                    <div class="sx-range" aria-hidden="false">
                        <div class="sx-track"><i class="sx-fill"></i></div>
                        <input type="range" class="sx-r1" min="{{ $pmin }}" max="{{ $pmax }}" step="{{ $step }}" value="{{ min(max($vmin, $pmin), $pmax) }}" aria-label="{{ __('Min price') }}" data-no-auto>
                        <input type="range" class="sx-r2" min="{{ $pmin }}" max="{{ $pmax }}" step="{{ $step }}" value="{{ min(max($vmax, $pmin), $pmax) }}" aria-label="{{ __('Max price') }}" data-no-auto>
                    </div>
                    <div class="tq-fprice">
                        <input type="number" min="0" step="any" name="price_min" value="{{ $f['price_min'] ?? '' }}" placeholder="{{ $pmin }}" class="form-control form-control-sm" aria-label="{{ __('Min price') }}">
                        <input type="number" min="0" step="any" name="price_max" value="{{ $f['price_max'] ?? '' }}" placeholder="{{ $pmax }}" class="form-control form-control-sm" aria-label="{{ __('Max price') }}">
                    </div>
                    <div class="sx-quick">@foreach ($quick as [$lo, $hi])<button type="button" data-lo="{{ $lo }}" data-hi="{{ $hi }}">{{ $hi === null ? __('Over :n', ['n' => number_format($lo)]) : ($lo == 0 ? __('Under :n', ['n' => number_format($hi)]) : number_format($lo).'–'.number_format($hi)) }}</button>@endforeach</div>
                </section>

                <a class="tq-fclear" href="{{ url()->current() }}{{ filled($f['q'] ?? null) ? '?q='.urlencode($f['q']) : '' }}">{{ __('Clear all') }}</a>
                <noscript><button class="btn btn-primary btn-sm mt-3 w-100">{{ __('Apply') }}</button></noscript>
                <div class="sh-ffoot d-lg-none"><a href="{{ url()->current() }}{{ filled($f['q'] ?? null) ? '?q='.urlencode($f['q']) : '' }}" class="sh-fclear">{{ __('Clear all') }}</a><button type="submit" class="sh-fapply">{{ __('Show :n results', ['n' => $total]) }}</button></div>
            </form>
            </div>
        </aside>

        <div class="mk-shop-main">
            <div class="sh-bar">
                <button class="sh-fbtn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sfFilters" aria-controls="sfFilters"><i class="bi bi-sliders2"></i> {{ __('Filters') }} @if ($active->isNotEmpty())<b>{{ $active->count() }}</b>@endif</button>
                <p class="sh-count" aria-live="polite">@if ($total){{ __('Showing :from–:to of :total', ['from' => $from, 'to' => $to, 'total' => $total]) }}@else{{ __('No products found') }}@endif</p>
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
            @if ($active->isNotEmpty() || filled($f['q'] ?? null))
                <div class="sh-chips" aria-label="{{ __('Active filters') }}">
                    @if (filled($f['q'] ?? null))<a class="mk-chip" href="{{ request()->fullUrlWithQuery(['q' => null, 'page' => null]) }}"><i class="bi bi-search"></i> “{{ $f['q'] }}” <i class="bi bi-x"></i></a>@endif
                    @foreach ($active as $chip)<a class="mk-chip" href="{{ $chip['url'] }}">{{ $chip['label'] }} <i class="bi bi-x"></i></a>@endforeach
                    <a class="sh-clear" href="{{ url()->current() }}">{{ __('Clear all') }}</a>
                </div>
            @endif

            @if ($products->isEmpty())
                <div class="sh-empty">
                    <span class="sh-orb"><i class="bi bi-search"></i></span>
                    <h2>{{ __('No products found') }}</h2>
                    <p>{{ __('Try removing a filter or searching for something else.') }}</p>
                    <div class="sh-empty-cta">@if ($active->isNotEmpty() || filled($f['q'] ?? null))<a href="{{ route('store.shop') }}" class="sh-btn">{{ __('Clear filters') }}</a>@endif
                        @if (\App\Support\BusinessProfile::hasModule('prescriptions'))<a href="{{ route('store.prescription.upload') }}" class="sh-btn ghost"><i class="bi bi-camera"></i> {{ __('Upload prescription') }}</a>@endif</div>
                    <div class="sh-empty-cats">@foreach (\App\Support\Storefront::tree()->take(6) as $c)<a href="{{ route('store.category', $c->slug) }}">{{ $c->trans('name') }}</a>@endforeach</div>
                </div>
            @else
                <div data-product-list class="sh-grid">
                    @include('store._grid', ['products' => $products, 'cols' => 'row-cols-xl-'.max(2, (int) sf('cards.cols') - 1)])
                </div>
                <div class="sh-pager">
                    <div class="sh-prog" role="img" aria-label="{{ __('Showing :from–:to of :total', ['from' => $from, 'to' => $to, 'total' => $total]) }}"><i style="width:{{ $total ? round($to / $total * 100) : 0 }}%"></i></div>
                    <small>{{ __('You have seen :to of :total', ['to' => $to, 'total' => $total]) }}</small>
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
