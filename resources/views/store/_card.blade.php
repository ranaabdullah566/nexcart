@php
    $out = $p->track_stock && $p->totalStock() <= 0;
    $needsOptions = $p->isVariable();
    $off = (! $p->isVariable() && $p->compare_price && $p->compare_price > $p->price) ? (int) round((1 - $p->price / $p->compare_price) * 100) : null;
    $thumb = $p->thumbUrl();
@endphp
<article class="mk-card sf-card" data-saved-item>
    <div class="mk-card-media">
        <a href="{{ route('store.product', $p->slug) }}" class="fx-img-link" tabindex="-1" aria-hidden="true">@if ($thumb)<img src="{{ $thumb }}" alt="" loading="lazy">@else<span class="ph"><i class="bi bi-box-seam"></i></span>@endif</a>
        @if ($off && sf('cards.show_discount'))<span class="mk-disc">−{{ $off }}%</span>@endif
        @if ($out)<span class="mk-flag out">{{ __('Out of stock') }}</span>@elseif ($p->requires_prescription)<span class="mk-flag rx"><i class="bi bi-prescription2"></i> Rx</span>@else<span class="mk-flag ok"><i class="bi bi-patch-check-fill"></i> {{ __('Genuine') }}</span>@endif
    </div>
    <div class="mk-card-body">
        <div class="mk-card-cat">@if ($p->category && sf('cards.show_category')){{ $p->category->trans('name') }}@else&nbsp;@endif</div>
        <a href="{{ route('store.product', $p->slug) }}" class="mk-card-name">{{ $p->trans('name') }}</a>
        @php $salt = trim((string) ($p->specs['composition'] ?? '')); $form = trim((string) ($p->specs['dosage_form'] ?? '')); $strength = trim((string) ($p->specs['strength'] ?? '')); @endphp
        @if ($salt !== '' && $salt !== '—')<div class="fx-salt" title="{{ $salt }}"><i class="bi bi-eyedropper"></i><span>{{ \Illuminate\Support\Str::limit($salt, 38) }}</span></div>@else<div class="fx-salt is-empty" aria-hidden="true"></div>@endif
        @if (($form !== '' && $form !== 'Other') || ($strength !== '' && $strength !== '—') || $p->isVariable())<div class="fx-chips">@if ($form !== '' && $form !== 'Other')<span>{{ $form }}</span>@endif @if ($strength !== '' && $strength !== '—')<span>{{ $strength }}</span>@endif @if ($p->isVariable())<span class="alt">{{ __('Pack sizes') }}</span>@endif</div>@else<div class="fx-chips is-empty" aria-hidden="true"></div>@endif
        @if (sf('cards.show_rating') && ($p->approved_reviews_count ?? 0) > 0)<div class="mk-card-rate"><span class="sf-stars" aria-hidden="true">@include('store._stars', ['value' => (float) $p->approved_reviews_avg_rating])</span><span class="figure">{{ number_format((float) $p->approved_reviews_avg_rating, 1) }} ({{ $p->approved_reviews_count }})</span></div>@else<div class="mk-card-rate is-empty" aria-hidden="true"></div>@endif
        <div class="tq-seller"><i class="bi bi-check2"></i> {{ __('Genuine product') }}@if ($p->brand) · {{ $p->brand->name }}@endif</div>
        <div class="mk-price">
            @if ($p->isVariable())<small>{{ __('from') }}</small>@endif
            <b>{{ money($p->displayPrice()) }}</b>
            <s class="@if (! $off) is-empty @endif" @if (! $off) aria-hidden="true" @endif>@if ($off){{ money($p->compare_price) }}@else&nbsp;@endif</s>
        </div>
    </div>
    <div class="mk-card-foot">
        @if ($out)
            <button class="mk-add" disabled>{{ __('Out of stock') }}</button>
        @elseif ($needsOptions)
            <button type="button" class="mk-add" data-quick="{{ route('store.quick', $p->slug) }}"><i class="bi bi-sliders2"></i> {{ __('Choose options') }}</button>
        @else
            <button type="button" class="mk-add" data-add="{{ $p->id }}"><i class="bi bi-bag-plus"></i> {{ sft('cards.add_label') }}</button>
        @endif
        <div class="tq-actions">
            @if (sf('cards.show_wish'))<button type="button" class="tq-act @if (app(\App\Services\Wishlist::class)->has($p->id)) on @endif" data-wish="{{ $p->id }}" aria-label="{{ __('Save') }}" aria-pressed="{{ app(\App\Services\Wishlist::class)->has($p->id) ? 'true' : 'false' }}" title="{{ __('Save') }}"><i class="bi bi-heart"></i></button>@endif
            @if (sf('cards.show_compare'))<button type="button" class="tq-act @if (app(\App\Services\Compare::class)->has($p->id)) on @endif" data-compare-toggle="{{ $p->id }}" aria-label="{{ __('Compare') }}" aria-pressed="{{ app(\App\Services\Compare::class)->has($p->id) ? 'true' : 'false' }}" title="{{ __('Compare') }}"><i class="bi bi-arrow-left-right"></i></button>@endif
            @if (! $out && ! $needsOptions)<button type="button" class="tq-act" data-add="{{ $p->id }}" aria-label="{{ __('Add to cart') }}" title="{{ __('Add to cart') }}"><i class="bi bi-cart3"></i></button>@endif
            @if (sf('cards.show_quick'))<button type="button" class="tq-act" data-quick="{{ route('store.quick', $p->slug) }}" aria-label="{{ __('Quick view') }}" title="{{ __('Quick view') }}"><i class="bi bi-grid"></i></button>@endif
        </div>
    </div>
</article>
