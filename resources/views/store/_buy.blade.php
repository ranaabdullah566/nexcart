@php
    $wished = app(\App\Services\Wishlist::class)->has($product->id);
    $variants = $product->isVariable() ? $product->variants->map(fn ($v) => [
        'id' => $v->id, 'price' => (float) $v->price, 'compare' => $v->compare_price ? (float) $v->compare_price : null,
        'stock' => $product->track_stock ? $v->stock_qty : 9999,
    ])->values() : collect();
    $firstAvail = $product->isVariable() ? ($product->variants->first(fn ($v) => ! $product->track_stock || $v->stock_qty > 0)?->id) : null;
    $stock = $product->isVariable() ? 9999 : ($product->track_stock ? $product->stock_qty : 9999);
@endphp
<form data-buy data-product="{{ $product->id }}" data-variable="{{ $product->isVariable() ? 1 : 0 }}" data-variants='@json($variants)'
      data-price="{{ (float) $product->price }}" data-compare="{{ $product->compare_price }}" data-track="{{ $product->track_stock ? 1 : 0 }}" data-stock="{{ $stock }}" novalidate>
    <div class="sf-price mb-2">
        @if ($product->isVariable())<small>{{ __('from') }}</small>@endif
        <b data-price>{{ money($product->isVariable() ? $product->displayPrice() : $product->price) }}</b>
        <s data-compare @if (! ($product->compare_price && $product->compare_price > $product->price && ! $product->isVariable())) hidden @endif>{{ $product->compare_price ? money($product->compare_price) : '' }}</s>
    </div>

    @if ($product->isVariable())
        <div class="sf-opt-title">{{ __('Choose an option') }}</div>
        <div class="sf-pills" role="radiogroup">
            @foreach ($product->variants as $v)
                @php($dead = $product->track_stock && $v->stock_qty <= 0)
                <label class="sf-pill"><input type="radio" name="variant_id" value="{{ $v->id }}" @disabled($dead) @checked($v->id === $firstAvail)><span>{{ $v->name }} <em>{{ money($v->price) }}</em></span></label>
            @endforeach
        </div>
    @endif

    @foreach ($product->modifierGroups as $g)
        @php($single = $g->max_select === 1)
        <div class="sf-opt-title"><span>{{ $g->trans('name') }}</span><small>@if ($g->min_select > 0){{ __('Required') }}@else{{ __('Optional') }}@endif @if (! $single)· {{ __('up to :n', ['n' => $g->max_select]) }}@endif</small></div>
        <div class="sf-pills" data-group data-max="{{ $g->max_select }}" role="{{ $single ? 'radiogroup' : 'group' }}">
            @if ($single && $g->min_select === 0)
                <label class="sf-pill"><input type="radio" name="grp{{ $g->id }}" value="" checked><span>{{ __('None') }}</span></label>
            @endif
            @foreach ($g->options as $o)
                <label class="sf-pill">
                    <input type="{{ $single ? 'radio' : 'checkbox' }}" name="grp{{ $g->id }}{{ $single ? '' : '[]' }}" value="{{ $o->id }}" data-delta="{{ (float) $o->price_delta }}"
                           @checked($single && $g->min_select > 0 && $loop->first || $o->is_default)>
                    <span>{{ $o->trans('name') }} @if ($o->price_delta != 0)<em>{{ $o->price_delta > 0 ? '+' : '−' }}{{ money(abs($o->price_delta)) }}</em>@endif</span>
                </label>
            @endforeach
        </div>
    @endforeach

    @if ($product->requires_prescription)
        <div class="sf-alert mt-3"><i class="bi bi-prescription2 fs-5"></i><span>{{ __('This item needs a valid prescription. You will upload it at checkout.') }}</span></div>
    @endif

    <div class="d-flex align-items-center gap-3 flex-wrap mt-4">
        <div class="sf-qty"><button type="button" data-q="-1" aria-label="-">−</button><input type="number" name="qty" value="1" min="1" max="99" inputmode="numeric" aria-label="{{ __('Quantity') }}"><button type="button" data-q="1" aria-label="+">+</button></div>
        <button type="submit" class="btn btn-primary btn-lg flex-grow-1" data-buy-btn><i class="bi bi-bag-plus me-1"></i> <span>{{ __('Add to cart') }}</span></button>
        <button type="button" class="sf-icon-btn @if ($wished) on @endif sf-tool" data-wish="{{ $product->id }}" style="width:48px;height:48px;border-radius:14px" aria-label="{{ __('Wishlist') }}" aria-pressed="{{ $wished ? 'true' : 'false' }}"><i class="bi bi-heart"></i></button>
    </div>
    <div class="d-flex justify-content-between mt-2 small">
        <span class="text-warning" data-stock-note></span>
        <span class="text-muted">{{ __('Total') }}: <b class="figure text-body" data-total></b></span>
    </div>
</form>
