@extends('layouts.admin')
@section('title', __($title).' · '.__('Reports'))

@php
    $fmt = function ($v, $t) {
        return match ($t) { 'money' => money($v), 'int' => number_format((float) $v), 'pct' => $v.'%', 'dec' => number_format((float) $v, 2), default => (string) ($v ?? '') };
    };
@endphp
@section('content')
<x-page-header :title="__($title)" :eyebrow="__('Reports')" :sub="local_time($from, 'd M Y').' → '.local_time($to, 'd M Y')" />
@include('admin.reports._nav', ['active' => $type])

<form method="GET" class="d-flex gap-2 flex-wrap align-items-end mb-4" data-no-ajax>
    <div class="btn-group" role="group">@foreach (['7' => __('7 days'), '30' => __('30 days'), '90' => __('90 days'), '365' => __('1 year'), 'custom' => __('Custom')] as $k => $l)<a class="btn btn-outline-secondary {{ $key === $k ? 'active' : '' }}" href="{{ route('admin.reports.show', [$type] + ($k === 'custom' ? ['range' => 'custom', 'from' => local_time($from, 'Y-m-d'), 'to' => local_time($to, 'Y-m-d')] : ['range' => $k])) }}" data-no-modal>{{ $l }}</a>@endforeach</div>
    @if ($key === 'custom')
        <input type="hidden" name="range" value="custom">
        <div><label class="form-label small mb-0">{{ __('From') }}</label><input type="date" name="from" value="{{ local_time($from, 'Y-m-d') }}" class="form-control form-control-sm"></div>
        <div><label class="form-label small mb-0">{{ __('To') }}</label><input type="date" name="to" value="{{ local_time($to, 'Y-m-d') }}" class="form-control form-control-sm"></div>
        <button class="btn btn-sm btn-primary">{{ __('Apply') }}</button>
    @endif
</form>

<div class="row g-3 mb-3 stagger">
    @foreach ($report['kpis'] as [$label, $value, $t, $change])
        <div class="col-6 col-lg-3"><x-stat-card :label="__($label)" icon="activity" :value="$fmt($value, $t)" :sub="$change === null ? '' : (($change >= 0 ? '+' : '').$change.'% '.__('vs previous period'))" /></div>
    @endforeach
</div>

<div class="row g-3 mb-3">
    @foreach ($report['charts'] as $c)
        <div class="col-lg-{{ count($report['charts']) === 1 ? 12 : (($loop->first && count($report['charts']) === 3) ? 12 : 6) }}"><div class="card h-100"><div class="card-body">
            <h5 class="mb-3">{{ __($c['title']) }}</h5>
            <div style="height:{{ ($c['horizontal'] ?? false) ? 320 : 260 }}px"><canvas class="nx-rchart" data-chart='@json($c)' role="img" aria-label="{{ __($c['title']) }}"></canvas></div>
        </div></div></div>
    @endforeach
</div>

@foreach ($report['tables'] as $t)
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0">{{ __($t['title']) }}</h5>
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
        var palette = ['#22d3ee', '#8b5cf6', '#34d399', '#fbbf24', '#f472b6', '#60a5fa', '#fb7185', '#a3e635'];
        document.querySelectorAll('.nx-rchart').forEach(function (cv) {
            var c = JSON.parse(cv.dataset.chart), pie = c.type === 'doughnut';
            var ds = c.datasets.map(function (d, i) {
                return Object.assign({}, d, pie ? { backgroundColor: palette, borderWidth: 0 } : { borderColor: palette[i], backgroundColor: c.type === 'line' ? 'rgba(34,211,238,.15)' : palette[i], fill: c.type === 'line', tension: .3, borderRadius: 6 });
            });
            new Chart(cv, { type: c.type, data: { labels: c.labels, datasets: ds }, options: {
                responsive: true, maintainAspectRatio: false, indexAxis: c.horizontal ? 'y' : 'x',
                plugins: { legend: { display: pie }, tooltip: { callbacks: { label: function (x) { var v = x.parsed.y !== undefined && !c.horizontal ? x.parsed.y : (x.parsed.x !== undefined && c.horizontal ? x.parsed.x : x.parsed); return (c.money ? cur + ' ' : '') + Number(v).toLocaleString(); } } } },
                scales: pie ? {} : { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.12)' } } }
            } });
        });
    })();
</script>
@endpush
