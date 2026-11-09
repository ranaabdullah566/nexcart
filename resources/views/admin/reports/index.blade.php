@extends('layouts.admin')
@section('title', __('Reports'))

@php
    $pct = fn ($c) => $c === null ? null : ($c >= 0 ? '+' : '').$c.'%';
    $tone = fn ($c) => $c === null ? '' : ($c >= 0 ? 'text-success' : 'text-danger');
@endphp
@section('content')
<x-page-header :title="__('Reports')" :eyebrow="__('Growth')" :sub="local_time($from, 'd M Y').' → '.local_time($to, 'd M Y')">
    <x-slot:actions>
        <a href="{{ route('admin.reports.export', request()->query()) }}" class="btn btn-outline-secondary" data-no-modal><i class="bi bi-download me-1"></i> {{ __('Export orders (CSV)') }}</a>
    </x-slot:actions>
</x-page-header>
@include('admin.reports._nav', ['active' => 'overview'])

<form method="GET" class="d-flex gap-2 flex-wrap align-items-end mb-4" data-no-ajax>
    <div class="btn-group" role="group">@foreach (['7' => __('7 days'), '30' => __('30 days'), '90' => __('90 days'), 'custom' => __('Custom')] as $k => $l)<a class="btn btn-outline-secondary {{ $key === $k ? 'active' : '' }}" href="{{ route('admin.reports.index', $k === 'custom' ? ['range' => 'custom', 'from' => local_time($from, 'Y-m-d'), 'to' => local_time($to, 'Y-m-d')] : ['range' => $k]) }}" data-no-modal>{{ $l }}</a>@endforeach</div>
    @if ($key === 'custom')
        <input type="hidden" name="range" value="custom">
        <div><label class="form-label small mb-0">{{ __('From') }}</label><input type="date" name="from" value="{{ local_time($from, 'Y-m-d') }}" class="form-control form-control-sm"></div>
        <div><label class="form-label small mb-0">{{ __('To') }}</label><input type="date" name="to" value="{{ local_time($to, 'Y-m-d') }}" class="form-control form-control-sm"></div>
        <button class="btn btn-sm btn-primary">{{ __('Apply') }}</button>
    @endif
</form>

<div class="row g-3 mb-3 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Revenue')" icon="cash-coin" tone="ok" :value="money($kpi['revenue'])" :sub="($pct($kpi['revenue_change']) ?? '—').' '.__('vs previous period')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Orders')" icon="receipt-cutoff" :value="$kpi['orders']" :sub="($pct($kpi['orders_change']) ?? '—').' '.__('vs previous period')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Average order')" icon="graph-up-arrow" tone="vio" :value="money($kpi['aov'])" :sub="trans_choice(':n item sold|:n items sold', $kpi['items'], ['n' => $kpi['items']])" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Customers')" icon="people-fill" tone="warn" :value="$kpi['customers']" :sub="__(':n new · :p% returning', ['n' => $kpi['new'], 'p' => $kpi['repeat']])" /></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8"><div class="card h-100"><div class="card-body"><h2 class="mb-3 h5">{{ __('Sales over time') }}</h2><div style="height:280px"><canvas id="salesChart" role="img" aria-label="{{ __('Sales over time') }}"></canvas></div></div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-body"><h2 class="mb-3 h5">{{ __('Orders by status') }}</h2>
        @if ($statuses)<div style="height:220px"><canvas id="statusChart" role="img" aria-label="{{ __('Orders by status') }}"></canvas></div>@else<div class="text-muted small py-4">{{ __('No orders in this period.') }}</div>@endif
    </div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7"><div class="card h-100"><div class="card-body"><h2 class="mb-3 h5">{{ __('Best sellers') }}</h2>
        <table class="table align-middle mb-0"><thead><tr><th>{{ __('Product') }}</th><th class="text-end">{{ __('Sold') }}</th><th class="text-end">{{ __('Revenue') }}</th></tr></thead><tbody>
        @forelse ($top as $t)<tr><td class="fw-semibold">{{ $t['name'] }}</td><td class="text-end figure">{{ $t['qty'] }}</td><td class="text-end figure">{{ money($t['revenue']) }}</td></tr>@empty<tr><td colspan="3" class="text-muted">{{ __('No sales yet.') }}</td></tr>@endforelse
        </tbody></table></div></div></div>
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
        <h2 class="mb-3 h5">{{ __('Sales by category') }}</h2>
        @forelse ($categories as $name => $amt)
            <div class="mb-2"><div class="d-flex justify-content-between small"><span>{{ $name }}</span><b class="figure">{{ money($amt) }}</b></div><div class="progress" style="height:6px;background:var(--surface-2)"><div class="progress-bar" style="width:{{ $kpi['revenue'] > 0 ? round($amt / max(1, array_sum($categories)) * 100) : 0 }}%;background:var(--grad)"></div></div></div>
        @empty<div class="text-muted small">{{ __('No sales yet.') }}</div>@endforelse
        <h3 class="mt-4 mb-2 text-uppercase small text-muted h6">{{ __('Payment methods') }}</h3>
        @foreach ($payments as $m => $p)<div class="d-flex justify-content-between small py-1"><span>{{ ['cod' => __('Cash on delivery'), 'bank' => __('Bank transfer'), 'stripe' => __('Card (Stripe)')][$m] ?? $m }} · {{ $p['orders'] }}</span><b class="figure">{{ money($p['total']) }}</b></div>@endforeach
        @if ($coupons)<h3 class="mt-4 mb-2 text-uppercase small text-muted h6">{{ __('Coupons used') }}</h3>@foreach ($coupons as $code => $c)<div class="d-flex justify-content-between small py-1"><span class="figure">{{ $code }} · {{ $c['orders'] }}</span><b class="figure text-success">−{{ money($c['discount']) }}</b></div>@endforeach @endif
    </div></div></div>
</div>

<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><h2 class="mb-0 h5">{{ __('Abandoned checkouts') }}</h2>
        <div class="small text-muted">{{ __(':c captured · :r reminded · :v recovered · :w still open', ['c' => $abandoned['captured'], 'r' => $abandoned['reminded'], 'v' => $abandoned['recovered'], 'w' => money($abandoned['open_value'])]) }}</div></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>{{ __('Customer') }}</th><th>{{ __('Cart') }}</th><th>{{ __('Last seen') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
    @forelse ($abandoned['rows'] as $a)
        <tr><td>{{ $a->email }}</td><td class="figure">{{ $a->items_count }} · {{ money($a->total) }}</td><td class="small text-muted">{{ local_time($a->last_seen_at, 'd M, H:i') }}</td>
            <td>@if ($a->recovered_at)<span class="badge rounded-pill text-bg-success">{{ __('Ordered') }}</span>@elseif ($a->opted_out)<span class="badge rounded-pill text-bg-secondary">{{ __('Opted out') }}</span>@elseif ($a->reminded_at)<span class="badge rounded-pill text-bg-info">{{ __('Reminded') }}</span>@else<span class="badge rounded-pill text-bg-warning">{{ __('Waiting') }}</span>@endif</td></tr>
    @empty<tr><td colspan="4" class="text-muted">{{ __('Nothing yet. Carts are captured when a customer types their e-mail at checkout.') }}</td></tr>@endforelse
    </tbody></table></div>
</div></div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
$(function () {
    if (!window.Chart) return;
    var d = @json($chart), st = @json($statuses), sl = @json(collect($statuses)->keys()->map(fn ($s) => __(str_replace('_', ' ', ucfirst($s))))->all());
    var css = function (v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); }, charts = [];
    function build() {
        charts.forEach(function (c) { c.destroy(); }); charts = [];
        var a1 = css('--acc-1') || '#22d3ee', a2 = css('--acc-2') || '#8b5cf6', grid = css('--border') || 'rgba(148,163,184,.2)', txt = css('--text-2') || '#9fb0c8';
        var el = document.getElementById('salesChart');
        if (el) charts.push(new Chart(el, { data: { labels: d.labels, datasets: [
            { type: 'line', label: @json(__('Revenue')), data: d.revenue, borderColor: a1, backgroundColor: a1 + '22', fill: true, tension: .35, pointRadius: 2, borderWidth: 2, yAxisID: 'y' },
            { type: 'bar', label: @json(__('Orders')), data: d.orders, backgroundColor: a2 + '88', borderRadius: 6, yAxisID: 'y1' }] },
            options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { labels: { color: txt } } },
                scales: { x: { grid: { display: false }, ticks: { color: txt, maxTicksLimit: 10 } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { color: txt } }, y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { color: txt, precision: 0 } } } } }));
        var s = document.getElementById('statusChart');
        if (s) charts.push(new Chart(s, { type: 'doughnut', data: { labels: sl, datasets: [{ data: Object.values(st), backgroundColor: [a1, a2, '#34d399', '#fbbf24', '#fb7185', '#60a5fa', '#a78bfa', '#94a3b8'], borderWidth: 0 }] }, options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { color: txt, boxWidth: 10 } } } } }));
    }
    build(); window.addEventListener('nx-theme', function () { setTimeout(build, 50); });
});
</script>
@endpush
