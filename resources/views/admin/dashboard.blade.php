@extends('layouts.admin')
@section('title', __('Dashboard'))
@push('styles')<link href="{{ asset('assets/css/dashboard.css') }}?v={{ @filemtime(public_path('assets/css/dashboard.css')) }}" rel="stylesheet">@endpush

@php
    $k = $sales['kpi'] ?? [];
    $delta = function (?float $d, bool $invert = false) {
        if ($d === null) { return '<span class="dz-delta up"><i class="bi bi-stars"></i> '.e(__('New')).'</span>'; }
        if (abs($d) < 0.05) { return '<span class="dz-delta flat">0%</span>'; }
        $good = ($d > 0) !== $invert;
        return '<span class="dz-delta '.($good ? 'up' : 'down').'"><i class="bi bi-arrow-'.($d > 0 ? 'up' : 'down').'-short"></i>'.e(abs($d)).'%</span>';
    };
    $attn = collect($attention)->filter(fn ($a) => $a[1] > 0);
@endphp

@section('content')
<x-page-header :title="__('Welcome back, :name', ['name' => explode(' ', auth()->user()->name)[0]])" :eyebrow="__('Overview')" :sub="__('Your store at a glance.')">
    <x-slot:actions>
        <div class="dz-quick">
            @can('products.create')<a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New product') }}</a>@endcan
            @can('reviews.manage')@if (\App\Support\BusinessProfile::hasModule('reviews'))<a href="{{ route('admin.reviews.create') }}" class="btn btn-outline-secondary"><i class="bi bi-chat-heart me-1"></i> {{ __('Add review') }}</a>@endif @endcan
            <a href="{{ route('store.home') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-up-right me-1"></i> {{ __('View store') }}</a>
        </div>
    </x-slot:actions>
</x-page-header>

{{-- things waiting for a person --}}
<div class="dz-attn" aria-label="{{ __('Needs attention') }}">
    @forelse ($attn as [$label, $n, $url, $icon, $tone])
        <a href="{{ $url }}" class="{{ $tone === 'bad' ? 'bad' : ($tone === 'vio' ? 'vio' : '') }}"><i class="bi bi-{{ $icon }}"></i>{{ $label }} <b>{{ $n }}</b></a>
    @empty
        <span class="dz-ok d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill border"><i class="bi bi-check-circle-fill"></i> {{ __('All caught up — nothing is waiting for you.') }}</span>
    @endforelse
</div>

@if ($canSales)
<div class="dz-kpis">
    <div class="dz-kpi" style="--k:var(--tq-blue, #1b74bb)"><div class="l">{{ __('Revenue today') }}<i class="bi bi-cash-coin"></i></div><div class="v">{{ money($k['revenue_today']) }}</div><div class="s">{!! $delta($k['revenue_today_delta']) !!} {{ __('vs yesterday') }}</div></div>
    <div class="dz-kpi" style="--k:var(--tq-orange, #c9590b)"><div class="l">{{ __('Orders today') }}<i class="bi bi-receipt-cutoff"></i></div><div class="v">{{ $k['orders_today'] }}</div><div class="s">{!! $delta($k['orders_today_delta']) !!} {{ __('vs yesterday') }}</div></div>
    <div class="dz-kpi" style="--k:var(--tq-green, #2f8a3b)"><div class="l">{{ __('Revenue (30 days)') }}<i class="bi bi-graph-up-arrow"></i></div><div class="v">{{ money($k['revenue_30']) }}</div><div class="s">{!! $delta($k['revenue_30_delta']) !!} {{ __('vs previous 30 days') }}</div></div>
    <div class="dz-kpi" style="--k:#7c3aed"><div class="l">{{ __('Orders (30 days)') }}<i class="bi bi-bag-check"></i></div><div class="v">{{ $k['orders_30'] }}</div><div class="s">{!! $delta($k['orders_30_delta']) !!} {{ __('vs previous 30 days') }}</div></div>
    <div class="dz-kpi" style="--k:#0e7490"><div class="l">{{ __('Average order') }}<i class="bi bi-calculator"></i></div><div class="v">{{ money($k['avg']) }}</div><div class="s">{!! $delta($k['avg_delta']) !!} {{ __('Last 30 days') }}</div></div>
    <div class="dz-kpi" style="--k:var(--tq-deal, #d9481c)"><div class="l">{{ __('Open orders') }}<i class="bi bi-hourglass-split"></i></div><div class="v">{{ $k['open'] }}</div><div class="s">{{ trans_choice(':n unpaid|:n unpaid', $k['unpaid'], ['n' => $k['unpaid']]) }}</div></div>
    <div class="dz-kpi" style="--k:#be123c"><div class="l">{{ __('Cancel rate') }}<i class="bi bi-x-octagon"></i></div><div class="v">{{ $k['cancel_rate'] }}%</div><div class="s">{{ __('Cancelled or refunded, 30 days') }}</div></div>
    <div class="dz-kpi" style="--k:#15803d"><div class="l">{{ __('Repeat buyers') }}<i class="bi bi-arrow-repeat"></i></div><div class="v">{{ $k['repeat_buyers'] }}<small class="fs-6 text-muted fw-normal"> / {{ $k['buyers'] }}</small></div><div class="s">{{ __('Signed-in buyers who ordered before') }}</div></div>
</div>

<div class="dz-grid dz-g-8-4">
    <div class="dz-card">
        <h2>{{ __('Sales trend') }}<span class="dz-range" role="group" aria-label="{{ __('Range') }}"><button type="button" data-range="7">7{{ __('d') }}</button><button type="button" data-range="14" class="on">14{{ __('d') }}</button><button type="button" data-range="30">30{{ __('d') }}</button><button type="button" data-range="90">90{{ __('d') }}</button></span></h2>
        <div class="sub">{{ __('Revenue (line) and number of orders (bars) per day') }}</div>
        <div class="dz-chart tall"><canvas id="cTrend" role="img" aria-label="{{ __('Sales trend') }}"></canvas></div>
    </div>
    <div class="dz-card">
        <h2>{{ __('Orders by status') }}</h2><div class="sub">{{ __('Last 30 days') }}</div>
        <div class="dz-chart"><canvas id="cStatus" role="img" aria-label="{{ __('Orders by status') }}"></canvas></div>
    </div>
</div>

<div class="dz-grid dz-g-3">
    <div class="dz-card"><h2>{{ __('Top products') }}</h2><div class="sub">{{ __('Units sold, last 30 days') }}</div>
        @if ($sales['topProducts'])<div class="dz-chart"><canvas id="cTop" role="img" aria-label="{{ __('Top products') }}"></canvas></div>@else<div class="dz-empty">{{ __('No sales yet.') }}</div>@endif</div>
    <div class="dz-card"><h2>{{ __('Sales by category') }}</h2><div class="sub">{{ __('Revenue, last 30 days') }}</div>
        @if ($sales['categories']['values'])<div class="dz-chart"><canvas id="cCat" role="img" aria-label="{{ __('Sales by category') }}"></canvas></div>@else<div class="dz-empty">{{ __('No sales yet.') }}</div>@endif</div>
    <div class="dz-card"><h2>{{ __('Payment methods') }}</h2><div class="sub">{{ __('Revenue, last 30 days') }}</div>
        @if ($sales['payments'])<div class="dz-chart"><canvas id="cPay" role="img" aria-label="{{ __('Payment methods') }}"></canvas></div>@else<div class="dz-empty">{{ __('No sales yet.') }}</div>@endif</div>
</div>

<div class="dz-grid dz-g-3">
    <div class="dz-card"><h2>{{ __('Busiest hours') }}</h2><div class="sub">{{ __('Orders by hour of day, last 90 days') }}</div><div class="dz-chart short"><canvas id="cHours" role="img" aria-label="{{ __('Busiest hours') }}"></canvas></div></div>
    <div class="dz-card"><h2>{{ __('Busiest days') }}</h2><div class="sub">{{ __('Orders by weekday, last 90 days') }}</div><div class="dz-chart short"><canvas id="cDays" role="img" aria-label="{{ __('Busiest days') }}"></canvas></div></div>
    <div class="dz-card"><h2>{{ __('Top cities') }}</h2><div class="sub">{{ __('Orders, last 30 days') }}</div>
        @if ($sales['cities']['values'])<div class="dz-chart short"><canvas id="cCity" role="img" aria-label="{{ __('Top cities') }}"></canvas></div>@else<div class="dz-empty">{{ __('No sales yet.') }}</div>@endif</div>
</div>
@endif

<div class="dz-grid dz-g-3">
    @if ($customers)
    <div class="dz-card">
        <h2>{{ __('New customers') }}<span class="dz-big fs-4">{{ $customers['new30'] }}</span></h2>
        <div class="sub">{!! $delta($customers['new30_delta']) !!} {{ __('last 30 days') }} · {{ __(':n customers in total', ['n' => $customers['total']]) }}</div>
        <div class="dz-chart short"><canvas id="cCust" role="img" aria-label="{{ __('New customers') }}"></canvas></div>
    </div>
    @endif
    @if ($reviews)
    <div class="dz-card">
        <h2>{{ __('Customer ratings') }}<a href="{{ route('admin.reviews.index') }}">{{ __('Reviews') }} →</a></h2>
        <div class="d-flex align-items-center gap-3 my-2"><span class="dz-big">{{ $reviews['avg'] ?: '–' }}</span><div><span class="sf-stars" style="color:#f59e0b">@include('store._stars', ['value' => $reviews['avg']])</span><div class="small text-muted">{{ trans_choice(':n review|:n reviews', $reviews['count'], ['n' => $reviews['count']]) }}</div></div></div>
        @foreach ($reviews['dist'] as $i => $n)@php($star = 5 - $i)<div class="dz-rate-row"><span>{{ $star }} ★</span><div class="dz-bar"><i style="width:{{ $reviews['count'] ? round($n / $reviews['count'] * 100) : 0 }}%"></i></div><span class="text-end">{{ $n }}</span></div>@endforeach
    </div>
    @endif
    @if ($customers && $customers['top'])
    <div class="dz-card">
        <h2>{{ __('Top customers') }}<a href="{{ route('admin.customers.index') }}">{{ __('All') }} →</a></h2><div class="sub">{{ __('By total spent') }}</div>
        <div class="dz-list">@foreach ($customers['top'] as $c)<div class="li"><span class="t"><b>{{ $c['name'] }}</b><small>{{ trans_choice(':n order|:n orders', $c['n'], ['n' => $c['n']]) }}</small></span><b class="r">{{ money($c['spent']) }}</b></div>@endforeach</div>
    </div>
    @endif
</div>

<div class="dz-grid dz-g-3">
    @if ($canSales)
    <div class="dz-card"><h2>{{ __('Recent orders') }}<a href="{{ route('admin.orders.index') }}">{{ __('All orders') }} →</a></h2>
        <div class="dz-list">@forelse ($recentOrders as $o)
            <a href="{{ route('admin.orders.show', $o) }}"><span class="t"><b class="figure">{{ $o->number }}</b><small>{{ $o->name }}</small></span><span class="r"><b class="figure d-block">{{ money($o->total) }}</b><span class="badge rounded-pill text-bg-secondary">{{ $o->statusLabel() }}</span></span></a>
        @empty<div class="dz-empty">{{ __('No orders yet.') }}</div>@endforelse</div></div>
    @endif
    @if ($stock)
    <div class="dz-card"><h2>{{ __('Stock alerts') }}<a href="{{ route('admin.inventory.index') }}">{{ __('Inventory') }} →</a></h2>
        <div class="sub">{{ __(':low low · :out out of stock · :n tracked', ['low' => $stock['low'], 'out' => $stock['out'], 'n' => $stock['tracked']]) }}</div>
        <div class="dz-list">@forelse ($stock['list'] as $p)
            <div class="li"><span class="t"><b>{{ $p->trans('name') }}</b><small>{{ __('Alert at :n', ['n' => $p->low_stock_threshold]) }}</small></span><span class="badge rounded-pill text-bg-{{ $p->totalStock() <= 0 ? 'danger' : 'warning' }} figure">{{ $p->totalStock() }}</span></div>
        @empty<div class="dz-empty"><i class="bi bi-check-circle-fill text-success"></i> {{ __('Stock levels look healthy.') }}</div>@endforelse</div></div>
    @endif
    @if ($reviews && $reviews['recent']->isNotEmpty())
    <div class="dz-card"><h2>{{ __('Latest reviews') }}<a href="{{ route('admin.reviews.create') }}">{{ __('Add review') }} +</a></h2>
        <div class="dz-list">@foreach ($reviews['recent'] as $r)
            <a href="{{ route('admin.reviews.edit', $r) }}"><span class="t"><b>{{ $r->product?->name }}</b><small>{{ $r->name }} · {{ \Illuminate\Support\Str::limit($r->body ?: $r->title, 40) }}</small></span><span class="r"><span class="sf-stars" style="color:#f59e0b">@include('store._stars', ['value' => $r->rating])</span></span></a>
        @endforeach</div></div>
    @endif
</div>

<div class="dz-card mb-3">
    <h2>{{ __('Store at a glance') }}</h2>
    <div class="dz-glance mt-2">
        <div><b>{{ $productCount }}</b><span>{{ __('Products in catalogue') }}</span></div>
        <div><b>{{ $staffCount }}</b><span>{{ __('Team members') }}</span></div>
        <div><b>{{ count($languages) }}</b><span>{{ collect($languages)->pluck('label')->implode(' · ') }}</span></div>
        <div><b>{{ __($profile['label']) }}</b><span>{{ __('Store type') }}</span></div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
$(function () {
    if (!window.Chart) return;
    var S = @json($sales), C = @json($customers), css = function (v, d) { return getComputedStyle(document.body).getPropertyValue(v).trim() || getComputedStyle(document.documentElement).getPropertyValue(v).trim() || d; };
    var charts = {}, range = 14, T = { revenue: @json(__('Revenue')), orders: @json(__('Orders')), units: @json(__('Units')) };
    var statusLabels = { pending: @json(__('Pending')), confirmed: @json(__('Confirmed')), preparing: @json(__('Preparing')), out_for_delivery: @json(__('Out for delivery')), ready: @json(__('Ready')), completed: @json(__('Completed')), cancelled: @json(__('Cancelled')), refunded: @json(__('Refunded')) };
    var days = [@json(__('Mon')), @json(__('Tue')), @json(__('Wed')), @json(__('Thu')), @json(__('Fri')), @json(__('Sat')), @json(__('Sun'))];
    function pal() { return [css('--tq-blue', '#1b74bb'), css('--tq-orange', '#c9590b'), css('--tq-green', '#2f8a3b'), '#7c3aed', '#0e7490', css('--tq-deal', '#d9481c'), '#64748b', '#be123c']; }
    function base() { return { maintainAspectRatio: false, animation: { duration: 500 }, plugins: { legend: { display: false }, tooltip: { padding: 10, cornerRadius: 8 } } }; }
    function axes(extra) { var g = css('--border', '#dde6f0'), t = css('--text-2', '#5d646b'); return Object.assign({ x: { grid: { display: false }, ticks: { color: t, maxRotation: 0, autoSkip: true } }, y: { beginAtZero: true, grid: { color: g }, ticks: { color: t, precision: 0 } } }, extra || {}); }
    function mk(id, cfg) { var el = document.getElementById(id); if (!el) return; if (charts[id]) charts[id].destroy(); charts[id] = new Chart(el, cfg); }
    function money(v) { return new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(v); }

    function trend() {
        if (!S) return; var P = pal(), n = range, l = S.series.labels.slice(-n), r = S.series.revenue.slice(-n), o = S.series.orders.slice(-n);
        var el = document.getElementById('cTrend'), g = el.getContext('2d').createLinearGradient(0, 0, 0, 300); g.addColorStop(0, P[0] + '55'); g.addColorStop(1, P[0] + '05');
        var cfg = base(); cfg.interaction = { mode: 'index', intersect: false }; cfg.plugins.legend = { display: true, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: css('--text-2', '#5d646b') } };
        cfg.scales = axes({ y1: { position: 'right', beginAtZero: true, grid: { display: false }, ticks: { color: css('--text-2', '#5d646b'), precision: 0 } } });
        mk('cTrend', { data: { labels: l, datasets: [
            { type: 'bar', label: T.orders, data: o, yAxisID: 'y1', backgroundColor: P[1] + 'cc', borderRadius: 5, maxBarThickness: 22, order: 2 },
            { type: 'line', label: T.revenue, data: r, borderColor: P[0], backgroundColor: g, fill: true, tension: .35, pointRadius: n > 30 ? 0 : 3, pointBackgroundColor: P[0], borderWidth: 2.5, order: 1 }] }, options: cfg });
    }
    function status() {
        if (!S) return; var keys = Object.keys(S.status), P = pal(), el = document.getElementById('cStatus'); if (!el) return;
        if (!keys.length) { el.parentNode.innerHTML = '<div class="dz-empty">' + @json(__('No orders yet.')) + '</div>'; return; }
        var cfg = base(); cfg.cutout = '66%'; cfg.plugins.legend = { display: true, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: css('--text-2', '#5d646b') } };
        mk('cStatus', { type: 'doughnut', data: { labels: keys.map(function (k) { return statusLabels[k] || k; }), datasets: [{ data: keys.map(function (k) { return S.status[k]; }), backgroundColor: keys.map(function (k, i) { return ({ completed: P[2], cancelled: '#be123c', refunded: '#64748b', pending: P[1] })[k] || P[i % P.length]; }), borderWidth: 2, borderColor: css('--surface', '#fff') }] }, options: cfg });
    }
    function bars(id, labels, data, horizontal, color, name) {
        var el = document.getElementById(id); if (!el) return; var cfg = base(); if (horizontal) cfg.indexAxis = 'y'; cfg.scales = axes(horizontal ? { x: { beginAtZero: true, grid: { color: css('--border', '#dde6f0') }, ticks: { color: css('--text-2', '#5d646b'), precision: 0 } }, y: { grid: { display: false }, ticks: { color: css('--text-2', '#5d646b') } } } : null);
        mk(id, { type: 'bar', data: { labels: labels, datasets: [{ label: name, data: data, backgroundColor: color, borderRadius: 6, maxBarThickness: horizontal ? 18 : 26 }] }, options: cfg });
    }
    function pay() {
        var el = document.getElementById('cPay'); if (!el || !S || !S.payments.length) return; var P = pal(), cfg = base(); cfg.plugins.legend = { display: true, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: css('--text-2', '#5d646b') } };
        cfg.plugins.tooltip.callbacks = { label: function (c) { return ' ' + c.label + ': ' + money(c.parsed) + ' (' + S.payments[c.dataIndex].n + ')'; } };
        mk('cPay', { type: 'pie', data: { labels: S.payments.map(function (p) { return p.label.replace(/_/g, ' ').toUpperCase(); }), datasets: [{ data: S.payments.map(function (p) { return p.sum; }), backgroundColor: P, borderWidth: 2, borderColor: css('--surface', '#fff') }] }, options: cfg });
    }
    function build() {
        var P = pal(); trend(); status();
        if (S) {
            if (S.topProducts.length) bars('cTop', S.topProducts.map(function (p) { return p.name.length > 18 ? p.name.slice(0, 17) + '…' : p.name; }), S.topProducts.map(function (p) { return p.qty; }), true, P[0], T.units);
            if (S.categories.values.length) bars('cCat', S.categories.labels, S.categories.values, true, P[2], T.revenue);
            pay();
            bars('cHours', Array.from({ length: 24 }, function (_, i) { return i; }), S.hours, false, P[1], T.orders);
            bars('cDays', days, S.weekdays, false, P[3], T.orders);
            if (S.cities.values.length) bars('cCity', S.cities.labels, S.cities.values, true, P[4], T.orders);
        }
        if (C) { var el = document.getElementById('cCust'); if (el) { var cfg = base(); cfg.scales = axes(); mk('cCust', { type: 'line', data: { labels: C.labels, datasets: [{ data: C.values, borderColor: P[2], backgroundColor: P[2] + '22', fill: true, tension: .35, pointRadius: 0, borderWidth: 2.5 }] }, options: cfg }); } }
    }
    document.querySelectorAll('[data-range]').forEach(function (b) { b.addEventListener('click', function () { range = +b.dataset.range; document.querySelectorAll('[data-range]').forEach(function (x) { x.classList.toggle('on', x === b); }); trend(); }); });
    build(); window.addEventListener('nx-theme', function () { setTimeout(build, 60); });
});
</script>
@endpush
