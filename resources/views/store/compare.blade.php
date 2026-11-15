@extends('layouts.store')
@php $page = 'compare'; @endphp
@section('title', __('Compare'))

@section('content')
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><span>{{ __('Compare') }}</span></nav>
    <div class="tq-page-title d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div><h1><span class="tq-emoji" aria-hidden="true">⇄</span> {{ __('Compare medicines') }}</h1><p>{{ __('Up to four medicines side by side.') }}</p></div>
        @if ($products->isNotEmpty())<form method="POST" action="{{ route('store.compare.clear') }}" data-no-validate>@csrf @method('DELETE')<button class="sf-btn-ghost">{{ __('Clear all') }}</button></form>@endif
    </div>
    @if ($products->count() < 1)
        <div class="sf-empty"><i class="bi bi-arrow-left-right"></i><h2 class="h5">{{ __('Nothing to compare yet') }}</h2><p class="text-muted">{{ __('Tap the compare icon on up to :n products to see them side by side.', ['n' => \App\Services\Compare::MAX]) }}</p><a href="{{ route('store.shop') }}" class="btn btn-primary mt-2">{{ __('Browse products') }}</a></div>
    @else
        <div class="tq-compare table-responsive">
            <table class="table align-top mb-0">
                <thead><tr><th class="tq-cmp-label"></th>@foreach ($products as $p)
                    <th class="tq-cmp-col">
                        <a href="{{ route('store.product', $p->slug) }}" class="tq-cmp-img">@if ($t = $p->thumbUrl())<img src="{{ $t }}" alt="" loading="lazy">@else<span class="ph"><i class="bi bi-box-seam"></i></span>@endif</a>
                        <a href="{{ route('store.product', $p->slug) }}" class="fw-semibold d-block mt-2">{{ $p->trans('name') }}</a>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 mt-1" data-compare-toggle="{{ $p->id }}" data-compare-remove>{{ __('Remove') }}</button>
                    </th>@endforeach</tr></thead>
                <tbody>
                    <tr><th>{{ __('Price') }}</th>@foreach ($products as $p)<td><b class="figure">{{ money($p->displayPrice()) }}</b>@if (! $p->isVariable() && $p->compare_price > $p->price)<s class="text-muted small ms-1">{{ money($p->compare_price) }}</s>@endif</td>@endforeach</tr>
                    <tr><th>{{ __('Category') }}</th>@foreach ($products as $p)<td>{{ $p->category?->trans('name') ?? '—' }}</td>@endforeach</tr>
                    <tr><th>{{ __('Rating') }}</th>@foreach ($products as $p)<td>@if ($p->approved_reviews_count)<span class="sf-stars">@include('store._stars', ['value' => (float) $p->approved_reviews_avg_rating])</span> <span class="small text-muted">({{ $p->approved_reviews_count }})</span>@else<span class="text-muted">—</span>@endif</td>@endforeach</tr>
                    <tr><th>{{ __('Availability') }}</th>@foreach ($products as $p)<td>@if ($p->track_stock && $p->totalStock() <= 0)<span class="text-danger">{{ __('Out of stock') }}</span>@else<span class="text-success">{{ __('In stock') }}</span>@endif</td>@endforeach</tr>
                    @foreach ($attrs as $a)
                        <tr><th>{{ $a->labelText() }}</th>@foreach ($products as $p)@php($v = $p->specs[$a->key] ?? null)<td>{{ $v === true ? __('Yes') : ($v === false ? __('No') : ($v ?? '—')) }}</td>@endforeach</tr>
                    @endforeach
                    <tr><th></th>@foreach ($products as $p)
                        <td>@php($needs = $p->isVariable())
                            @if ($p->track_stock && $p->totalStock() <= 0)<button class="sf-add" disabled>{{ __('Out of stock') }}</button>
                            @elseif ($needs)<a class="sf-add" href="{{ route('store.product', $p->slug) }}"><i class="bi bi-sliders2"></i> {{ __('Choose options') }}</a>
                            @else<button type="button" class="sf-add" data-add="{{ $p->id }}"><i class="bi bi-bag-plus"></i> {{ __('Add to cart') }}</button>@endif</td>
                    @endforeach</tr>
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
