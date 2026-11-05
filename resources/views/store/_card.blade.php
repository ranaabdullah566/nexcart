@php
    $out = $p->track_stock && $p->totalStock() <= 0;
    $needsOptions = $p->isVariable() || ($p->modifier_groups_count ?? 0) > 0;
    $off = (! $p->isVariable() && $p->compare_price && $p->compare_price > $p->price) ? (int) round((1 - $p->price / $p->compare_price) * 100) : null;
    $wished = app(\App\Services\Wishlist::class)->has($p->id);
    $compared = app(\App\Services\Compare::class)->has($p->id);
    $thumb = $p->thumbUrl();
@endphp
<article class="mk-card sf-card" data-wish-item>
    <a href="{{ route('store.product', $p->slug) }}" class="mk-card-media" tabindex="-1" aria-hidden="true">
        @if ($thumb)<img src="{{ $thumb }}" alt="" loading="lazy">@else<span class="ph"><i class="bi bi-box-seam"></i></span>@endif
        @if ($off && sf('cards.show_discount'))<span class="mk-disc">−{{ $off }}%</span>@endif
        @if ($out)<span class="mk-flag out">{{ __('Out of stock') }}</span>@elseif ($p->is_featured)<span class="mk-flag"><i class="bi bi-patch-check-fill"></i> {{ __('Featured') }}</span>@endif
        @if ($p->requires_prescription)<span class="mk-flag rx"><i class="bi bi-prescription2"></i> Rx</span>@endif
        @if (sf('cards.show_quick'))<button type="button" class="mk-qv" data-quick="{{ route('store.quick', $p->slug) }}" aria-label="{{ __('Quick view') }}" title="{{ __('Quick view') }}"><i class="bi bi-eye"></i></button>@endif
    </a>
    <div class="mk-card-body">
        @if ($p->category && sf('cards.show_category'))<div class="mk-card-cat">{{ $p->category->trans('name') }}</div>@endif
        <a href="{{ route('store.product', $p->slug) }}" class="mk-card-name">{{ $p->trans('name') }}</a>
        @if (sf('cards.show_rating') && ($p->approved_reviews_count ?? 0) > 0)<div class="mk-card-rate"><span class="sf-stars" aria-hidden="true">@include('store._stars', ['value' => (float) $p->approved_reviews_avg_rating])</span><span class="figure">{{ number_format((float) $p->approved_reviews_avg_rating, 1) }} ({{ $p->approved_reviews_count }})</span></div>@endif
        <div class="mk-price">
            @if ($p->isVariable())<small>{{ __('from') }}</small>@endif
            <b>{{ money($p->displayPrice()) }}</b>
            @if ($off)<s>{{ money($p->compare_price) }}</s>@endif
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
        @if (sf('cards.show_wish') || sf('cards.show_compare'))<div class="mk-card-icons">
            @if (sf('cards.show_wish'))<button type="button" class="mk-ico @if ($wished) on @endif" data-wish="{{ $p->id }}" aria-label="{{ __('Wishlist') }}" aria-pressed="{{ $wished ? 'true' : 'false' }}" title="{{ __('Save') }}"><i class="bi bi-heart"></i></button>@endif
            @if (sf('cards.show_compare'))<button type="button" class="mk-ico @if ($compared) on @endif" data-compare-toggle="{{ $p->id }}" aria-label="{{ __('Compare') }}" aria-pressed="{{ $compared ? 'true' : 'false' }}" title="{{ __('Compare') }}"><i class="bi bi-arrow-left-right"></i></button>@endif
        </div>@endif
    </div>
</article>
