@php
    $out = $p->track_stock && $p->totalStock() <= 0;
    $needsOptions = $p->isVariable() || ($p->modifier_groups_count ?? 0) > 0;
    $off = (! $p->isVariable() && $p->compare_price && $p->compare_price > $p->price) ? (int) round((1 - $p->price / $p->compare_price) * 100) : null;
    $wished = app(\App\Services\Wishlist::class)->has($p->id);
    $thumb = $p->thumbUrl();
@endphp
<article class="sf-card" data-wish-item>
    <a href="{{ route('store.product', $p->slug) }}" class="sf-card-media" tabindex="-1" aria-hidden="true">
        @if ($thumb)<img src="{{ $thumb }}" alt="" loading="lazy">@else<span class="ph"><i class="bi bi-box-seam"></i></span>@endif
    </a>
    <div class="sf-tags">
        @if ($off)<span class="sf-tag sale">-{{ $off }}%</span>@endif
        @if ($out)<span class="sf-tag out">{{ __('Out of stock') }}</span>@endif
        @if ($p->requires_prescription)<span class="sf-tag rx"><i class="bi bi-prescription2"></i> Rx</span>@endif
    </div>
    <div class="sf-card-tools">
        <button type="button" class="sf-tool @if ($wished) on @endif" data-wish="{{ $p->id }}" aria-label="{{ __('Wishlist') }}" aria-pressed="{{ $wished ? 'true' : 'false' }}"><i class="bi bi-heart"></i></button>
        <button type="button" class="sf-tool" data-quick="{{ route('store.quick', $p->slug) }}" aria-label="{{ __('Quick view') }}"><i class="bi bi-eye"></i></button>
    </div>
    <div class="sf-card-body">
        @if ($p->category)<div class="sf-card-cat">{{ $p->category->trans('name') }}</div>@endif
        <a href="{{ route('store.product', $p->slug) }}" class="sf-card-name">{{ $p->trans('name') }}</a>
        <div class="sf-price">
            @if ($p->isVariable())<small>{{ __('from') }}</small>@endif
            <b>{{ money($p->displayPrice()) }}</b>
            @if ($off)<s>{{ money($p->compare_price) }}</s>@endif
        </div>
        <div class="sf-card-foot">
            @if ($out)
                <button class="sf-add" disabled>{{ __('Out of stock') }}</button>
            @elseif ($needsOptions)
                <button type="button" class="sf-add" data-quick="{{ route('store.quick', $p->slug) }}"><i class="bi bi-sliders2"></i> {{ __('Choose options') }}</button>
            @else
                <button type="button" class="sf-add" data-add="{{ $p->id }}"><i class="bi bi-bag-plus"></i> {{ __('Add to cart') }}</button>
            @endif
        </div>
    </div>
</article>
