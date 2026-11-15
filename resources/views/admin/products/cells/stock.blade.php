@php($stock = $p->totalStock())
@if (! $p->track_stock)<span class="text-muted">∞</span>
@else<span class="figure {{ $p->isLowStock() ? 'text-warning fw-bold' : '' }}">{{ $stock }}</span>@endif
