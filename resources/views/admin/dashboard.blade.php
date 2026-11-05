@extends('layouts.admin')
@section('title', __('Dashboard'))

@section('content')
<x-page-header :title="__('Welcome back, :name', ['name' => explode(' ', auth()->user()->name)[0]])" :eyebrow="__('Overview')" :sub="__('Your store at a glance.')" />

<div class="row g-3 mb-4 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Business type')" icon="shop" :value="__($profile['label'])" :sub="__('Chosen at setup')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Products')" icon="box-seam-fill" tone="vio" :value="$productCount" :sub="__('In catalogue')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Staff')" icon="person-badge-fill" tone="ok" :value="$staffCount" :sub="__('Active team members')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Languages')" icon="translate" tone="warn" :value="count($languages)" :sub="collect($languages)->pluck('label')->implode(' · ')" /></div>
</div>

@if ($canSales)
<div class="row g-3 mb-3 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Orders today')" icon="receipt-cutoff" :value="$sales['today']" :sub="__('Placed since midnight')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Revenue (30 days)')" icon="cash-coin" tone="ok" :value="money($sales['revenue30'])" :sub="__('Excluding cancelled')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Open orders')" icon="hourglass-split" tone="warn" :value="$sales['open']" :sub="__('Need attention')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Average order')" icon="graph-up-arrow" tone="vio" :value="money($sales['avg'])" :sub="__('Last 30 days')" /></div>
</div>
<div class="row g-3 mb-3">
    <div class="col-lg-8"><div class="card h-100"><div class="card-body"><h5 class="mb-3">{{ __('Revenue, last 14 days') }}</h5><div style="height:260px"><canvas id="revChart" role="img" aria-label="{{ __('Revenue, last 14 days') }}"></canvas></div></div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">{{ __('Recent orders') }}</h5><a href="{{ route('admin.orders.index') }}" class="small">{{ __('All orders') }}</a></div>
        @forelse ($recentOrders as $o)
            <a href="{{ route('admin.orders.show', $o) }}" class="d-flex justify-content-between align-items-center py-2 border-bottom text-decoration-none" style="border-color:var(--border) !important;color:var(--text)">
                <span><b class="figure">{{ $o->number }}</b><span class="d-block small text-muted">{{ $o->name }}</span></span>
                <span class="text-end"><span class="figure d-block">{{ money($o->total) }}</span><span class="badge rounded-pill text-bg-secondary">{{ $o->statusLabel() }}</span></span>
            </a>
        @empty<div class="text-muted small py-3">{{ __('No orders yet.') }}</div>@endforelse
    </div></div></div>
</div>
@endif

@if ($lowStock->isNotEmpty())
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>{{ __('Low stock') }}</h5><a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Open inventory') }}</a></div>
    <div class="row g-2">
        @foreach ($lowStock as $p)
            <div class="col-md-6 col-xl-4"><div class="d-flex justify-content-between border rounded-3 px-3 py-2" style="border-color:var(--border) !important"><span class="text-truncate me-2">{{ $p->trans('name') }}</span><b class="figure {{ $p->totalStock() === 0 ? 'text-danger' : 'text-warning' }}">{{ $p->totalStock() }}</b></div></div>
        @endforeach
    </div>
</div></div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100"><div class="card-body">
            <h5 class="mb-3">{{ __('Enabled modules') }}</h5>
            <div class="d-flex flex-wrap gap-2">
                @forelse ($modules as $m)
                    <span class="badge rounded-pill text-bg-secondary px-3 py-2">{{ str($m)->replace('_', ' ')->title() }}</span>
                @empty
                    <span class="text-muted">{{ __('No modules enabled.') }}</span>
                @endforelse
            </div>
            @can('settings.update')
                <a href="{{ route('admin.settings.edit') }}" class="btn btn-outline-secondary btn-sm mt-3"><i class="bi bi-sliders me-1"></i> {{ __('Customise') }}</a>
            @endcan
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100"><div class="card-body">
            <h5 class="mb-3">{{ __('Next up') }}</h5>
            <ul class="list-unstyled mb-0 d-grid gap-2">
                <li><i class="bi bi-check-circle-fill text-success me-2"></i>{{ __('Store set up and admin ready') }}</li>
                <li><a href="{{ route('admin.products.index') }}" class="text-reset text-decoration-none"><i class="bi bi-{{ $productCount ? 'check-circle-fill text-success' : 'circle text-muted' }} me-2"></i>{{ __('Add categories and products') }}</a></li>
                <li><i class="bi bi-circle me-2 text-muted"></i>{{ __('Open your storefront') }}</li>
                <li><i class="bi bi-circle me-2 text-muted"></i>{{ __('Take your first order') }}</li>
            </ul>
        </div></div>
    </div>
</div>
@endsection

@if ($canSales)
@push('scripts')
<script nonce="{{ csp_nonce() }}">
    $(function () {
        var d = @json($chart), el = document.getElementById('revChart'); if (!el || !window.Chart) return;
        var css = function (v) { return getComputedStyle(document.documentElement).getPropertyValue(v).trim(); }, chart;
        function build() {
            if (chart) chart.destroy();
            var a1 = css('--acc-1') || '#22d3ee', a2 = css('--acc-2') || '#8b5cf6', grid = css('--border') || 'rgba(148,163,184,.2)', txt = css('--text-2') || '#9fb0c8';
            var g = el.getContext('2d').createLinearGradient(0, 0, 0, 260); g.addColorStop(0, a2 + 'aa'); g.addColorStop(1, a2 + '05');
            chart = new Chart(el, { type: 'line', data: { labels: d.labels, datasets: [{ label: @json(__('Revenue')), data: d.revenue, borderColor: a1, backgroundColor: g, fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: a1, borderWidth: 2 }] },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { color: txt } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { color: txt } } } } });
        }
        build(); window.addEventListener('nx-theme', function () { setTimeout(build, 50); });
    });
</script>
@endpush
@endif
