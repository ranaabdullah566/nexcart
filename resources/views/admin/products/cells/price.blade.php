<span class="figure text-nowrap">{{ $p->isVariable() ? __('from').' ' : '' }}{{ setting('store.currency_symbol') }} {{ number_format((float) $p->displayPrice(), 2) }}</span>
