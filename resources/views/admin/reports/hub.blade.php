@extends('layouts.admin')
@section('title', __($title).' · '.__('Reports'))

@php
    $fmt = function ($v, $t) {
        return match ($t) { 'money' => money($v), 'int' => number_format((float) $v), 'pct' => $v.'%', 'dec' => number_format((float) $v, 2), default => (string) ($v ?? '') };
    };
@endphp
@section('content')
@push('styles')<link href="{{ asset('assets/css/reports.css') }}?v={{ @filemtime(public_path('assets/css/reports.css')) }}" rel="stylesheet">@endpush
@php
    // plain-language highlights from the numbers that have a comparison
    $insights = collect($report['kpis'])->filter(fn ($k) => $k[3] !== null && abs($k[3]) >= 0.1)->sortByDesc(fn ($k) => abs($k[3]))->take(3)
        ->map(fn ($k) => ['up' => $k[3] >= 0, 'text' => __(':kpi is :dir :n% compared with the previous period (:v)', ['kpi' => __($k[0]), 'dir' => $k[3] >= 0 ? __('up') : __('down'), 'n' => abs($k[3]), 'v' => $fmt($k[1], $k[2])])]);
@endphp
<x-page-header :title="__($title)" :eyebrow="__('Reports')" :sub="local_time($from, 'd M Y').' → '.local_time($to, 'd M Y')">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> {{ __('Print / PDF') }}</button>
        @can('reports.export')<a class="btn btn-outline-secondary" data-no-modal href="{{ route('admin.reports.show', [$type] + request()->query() + ['export' => 'all']) }}"><i class="bi bi-file-earmark-spreadsheet me-1"></i> {{ __('Export all (CSV)') }}</a>@endcan
    </x-slot:actions>
</x-page-header>
<div class="rp-print-head"><b>{{ setting('store.name', config('app.name')) }}</b> — {{ __($title) }} · {{ local_time($from, 'd M Y') }} → {{ local_time($to, 'd M Y') }}@if (filled($filters['method'] ?? null) || filled($filters['city'] ?? null)) · {{ $filters['method'] ?? '' }} {{ $filters['city'] ?? '' }}@endif</div>
@include('admin.reports._nav', ['active' => $type])
@include('admin.reports._range', ['route' => 'admin.reports.show', 'routeParams' => ['type' => $type]])

@if ($insights->isNotEmpty())
<div class="rp-insights">@foreach ($insights as $i)<div class="rp-insight {{ $i['up'] ? 'up' : 'down' }}"><i class="bi bi-{{ $i['up'] ? 'graph-up-arrow' : 'graph-down-arrow' }}"></i><span>{{ $i['text'] }}</span></div>@endforeach</div>
@endif

<div class="rp-kpis">
    @foreach ($report['kpis'] as [$label, $value, $t, $change])
        <div class="rp-kpi"><div class="l">{{ __($label) }}</div><div class="v">{{ $fmt($value, $t) }}</div>
            @if ($change !== null)<div class="s {{ abs($change) < 0.05 ? 'flat' : ($change >= 0 ? 'up' : 'down') }}"><i class="bi bi-{{ $change >= 0 ? 'arrow-up' : 'arrow-down' }}-short"></i>{{ abs($change) }}% {{ __('vs previous period') }}</div>@endif
        </div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    @foreach ($report['charts'] as $c)
        <div class="col-lg-{{ count($report['charts']) === 1 ? 12 : (($loop->first && count($report['charts']) === 3) ? 12 : 6) }}"><div class="card h-100"><div class="card-body">
            <h2 class="mb-3 h5">{{ __($c['title']) }}</h2>
            <div style="height:{{ ($c['horizontal'] ?? false) ? 320 : 260 }}px"><canvas class="nx-rchart" data-chart='@json($c)' role="img" aria-label="{{ __($c['title']) }}"></canvas></div>
        </div></div></div>
    @endforeach
</div>

@foreach ($report['tables'] as $t)
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0 h5">{{ __($t['title']) }}</h2>
        @can('reports.export')@if ($t['rows'])<a class="btn btn-sm btn-outline-secondary" data-no-modal href="{{ route('admin.reports.show', [$type] + request()->query() + ['export' => $t['key']]) }}"><i class="bi bi-download me-1"></i> CSV</a>@endif @endcan
    </div>
    @if (empty($t['rows']))
        <div class="text-muted small py-2">{{ __('No data for this period.') }}</div>
    @else
        <div class="table-responsive"><table class="table align-middle mb-0 nx-dt" data-page-length="10">
            <thead><tr>@foreach ($t['cols'] as $i => $c)<th class="{{ in_array($t['types'][$i], ['money', 'int', 'pct', 'dec']) ? 'text-end' : '' }}">{{ __($c) }}</th>@endforeach</tr></thead>
            <tbody>@foreach ($t['rows'] as $row)<tr>@foreach ($row as $i => $v)<td class="{{ in_array($t['types'][$i], ['money', 'int', 'pct', 'dec']) ? 'text-end figure' : '' }}" data-order="{{ is_numeric($v) ? $v : '' }}">{{ $fmt($v, $t['types'][$i]) }}</td>@endforeach</tr>@endforeach</tbody>
        </table></div>
    @endif
</div></div>
@endforeach
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
    (function () {
        var cur = @json(setting('store.currency_symbol', 'Rs'));
        var cs = function (v, d) { var b = getComputedStyle(document.body).getPropertyValue(v).trim(); return b || d; };
        var palette = [cs('--tq-blue', '#22d3ee'), cs('--tq-orange', '#8b5cf6'), cs('--tq-green', '#34d399'), '#7c3aed', '#0e7490', cs('--tq-deal', '#fb7185'), '#64748b', '#be123c'];
        document.querySelectorAll('.nx-rchart').forEach(function (cv) {
            var c = JSON.parse(cv.dataset.chart), pie = c.type === 'doughnut';
            var ds = c.datasets.map(function (d, i) {
                return Object.assign({}, d, pie ? { backgroundColor: palette, borderWidth: 0 } : { borderColor: palette[i], backgroundColor: c.type === 'line' ? palette[i] + '22' : palette[i], fill: c.type === 'line' && i === 0, tension: .3, borderRadius: 6, borderDash: d.dashed ? [6, 4] : [], borderWidth: c.type === 'line' ? 2.5 : 0, pointRadius: d.dashed ? 0 : 2 });
            });
            new Chart(cv, { type: c.type, data: { labels: c.labels, datasets: ds }, options: {
                responsive: true, maintainAspectRatio: false, indexAxis: c.horizontal ? 'y' : 'x',
                plugins: { legend: { display: pie }, tooltip: { callbacks: { label: function (x) { var v = x.parsed.y !== undefined && !c.horizontal ? x.parsed.y : (x.parsed.x !== undefined && c.horizontal ? x.parsed.x : x.parsed); return (c.money ? cur + ' ' : '') + Number(v).toLocaleString(); } } } },
                scales: pie ? {} : { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.12)' }, ticks: { precision: c.money ? undefined : 0 } } }
            } });
        });
    })();
</script>
@endpush
