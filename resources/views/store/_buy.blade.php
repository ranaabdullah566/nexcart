@php
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
        @if (! $product->isVariable() && $product->compare_price && $product->compare_price > $product->price)<span class="tq-save">{{ __('Save :n%', ['n' => (int) round((1 - $product->price / $product->compare_price) * 100)]) }}</span>@endif
        <span class="tq-pill">{{ setting('store.tax_inclusive') ? __('Tax included') : __('Tax excluded') }}</span>
    </div>
    <div class="tq-total"><small>{{ __('Order total') }}</small><b class="figure" data-total></b></div>

    @if ($product->isVariable())
        <div class="sf-opt-title">{{ __('Choose an option') }}</div>
        <div class="sf-pills" role="radiogroup">
            @foreach ($product->variants as $v)
                @php($dead = $product->track_stock && $v->stock_qty <= 0)
                <label class="sf-pill"><input type="radio" name="variant_id" value="{{ $v->id }}" @disabled($dead) @checked($v->id === $firstAvail)><span>{{ $v->name }} <em>{{ money($v->price) }}</em></span></label>
            @endforeach
        </div>
    @endif

    @if ($product->requires_prescription)
        <div class="sf-alert mt-3"><i class="bi bi-prescription2 fs-5"></i><span>{{ __('This item needs a valid prescription. You will upload it at checkout.') }}</span></div>
    @endif

    <div class="d-flex align-items-center gap-3 flex-wrap mt-4">
        <div class="sf-qty"><button type="button" data-q="-1" aria-label="-">−</button><input type="number" name="qty" value="1" min="1" max="99" inputmode="numeric" aria-label="{{ __('Quantity') }}"><button type="button" data-q="1" aria-label="+">+</button></div>
        <button type="submit" class="btn btn-primary btn-lg flex-grow-1" data-buy-btn><i class="bi bi-bag-plus me-1"></i> <span>{{ __('Add to cart') }}</span></button>
    </div>
    <div class="d-flex justify-content-between mt-2 small">
        <span class="text-warning" data-stock-note></span>
        <span class="text-muted tq-poweredby">{{ __('Delivery is calculated at checkout.') }}</span>
    </div>
</form>
