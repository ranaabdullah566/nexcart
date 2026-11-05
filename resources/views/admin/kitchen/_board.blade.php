@php
    $titles = ['new' => ['New', 'inbox-fill'], 'preparing' => ['Preparing', 'fire'], 'ready' => ['Ready', 'bag-check-fill']];
    $labels = ['pending' => 'Accept', 'confirmed' => 'Start cooking', 'preparing' => 'Ready', 'ready' => 'Handed over'];
    $canAct = auth()->user()->can('orders.update');
@endphp
<div data-new="{{ $newCount }}"></div>
<div class="kds-cols">
@foreach ($columns as $key => $orders)
    <div class="kds-col">
        <h5><span><i class="bi bi-{{ $titles[$key][1] }} me-2"></i>{{ __($titles[$key][0]) }}</span><span class="badge rounded-pill text-bg-secondary">{{ $orders->count() }}</span></h5>
        @forelse ($orders as $o)
            @php
                $mins = (int) $o->placed_at->diffInMinutes(now());
                $cls = $o->awaitsPrescription() ? 'blocked' : ($mins >= 25 && $o->status !== 'out_for_delivery' ? 'late' : ($mins >= 15 && $o->status !== 'out_for_delivery' ? 'warn' : ''));
                $next = $o->nextStatuses()[0] ?? null;
                $kitchenCan = $canAct && ! $o->awaitsPrescription() && isset($labels[$o->status]) && ! ($o->status === 'ready' && ! $o->is_pickup) && $next && $next !== 'cancelled';
            @endphp
            <div class="kds-card {{ $cls }}">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div><span class="kds-num">{{ $o->number }}</span><div class="small text-muted">{{ $o->name }} · {{ $o->is_pickup ? __('Pickup') : __('Delivery') }}</div></div>
                    <div class="text-end"><div class="kds-timer {{ $cls === 'late' ? 'text-danger' : ($cls === 'warn' ? 'text-warning' : '') }}">{{ $mins }}′</div>
                        @if ($o->eta_at && $o->isOpen())<div class="small text-muted figure">{{ __('ETA') }} {{ $o->eta_at->timezone(setting('store.timezone'))->format('H:i') }}</div>@endif</div>
                </div>
                @foreach ($o->items as $i)
                    <div class="kds-item"><b>{{ $i->qty }}×</b><div>{{ $i->name }}@if ($i->variant_name) <span class="text-muted">· {{ $i->variant_name }}</span>@endif
                        @if ($i->options)<div class="kds-mod">+ {{ collect($i->options)->pluck('name')->implode(', ') }}</div>@endif</div></div>
                @endforeach
                @if ($o->notes)<div class="mt-2 small p-2 rounded-3" style="background:var(--surface-2)"><i class="bi bi-chat-left-text me-1"></i>{{ $o->notes }}</div>@endif
                @if ($o->slot_label)<div class="mt-2 small"><i class="bi bi-calendar-check me-1"></i>{{ $o->slot_date->format('d M') }} · {{ $o->slot_label }}</div>@endif
                @if ($o->awaitsPrescription())<div class="mt-2 small text-warning"><i class="bi bi-prescription2 me-1"></i>{{ __('Waiting for prescription approval') }}</div>@endif
                @if ($o->rider)<div class="mt-2 small"><i class="bi bi-bicycle me-1"></i>{{ $o->rider->name }}</div>@endif
                <div class="mt-3">
                    @if ($kitchenCan)
                        <form method="POST" action="{{ route('admin.kitchen.advance', $o) }}" data-no-ajax>@csrf<button class="btn btn-primary w-100 btn-lg">{{ __($labels[$o->status]) }} <i class="bi bi-arrow-right ms-1"></i></button></form>
                    @elseif ($o->status === 'ready' && ! $o->is_pickup)
                        <div class="text-center small text-muted"><i class="bi bi-hourglass-split me-1"></i>{{ $o->rider ? __('Waiting for :name to pick up', ['name' => $o->rider->name]) : __('Waiting for a rider') }}</div>
                    @elseif ($o->status === 'out_for_delivery')
                        <div class="text-center small text-muted"><i class="bi bi-bicycle me-1"></i>{{ __('On the way') }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center text-muted small py-4">{{ __('Nothing here') }}</div>
        @endforelse
    </div>
@endforeach
</div>
