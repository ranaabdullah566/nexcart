@extends('layouts.admin')
@section('title', __('Inventory'))

@section('content')
<x-page-header :title="__('Inventory')" :eyebrow="__('Catalog')" :sub="__('Stock levels, batches and every movement.')">
    <x-slot:actions>
        @can('inventory.adjust')<a href="{{ route('admin.inventory.adjust') }}" class="btn btn-primary"><i class="bi bi-arrow-left-right me-1"></i> {{ __('Adjust stock') }}</a>@endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Tracked items')" icon="clipboard2-data-fill" :value="$rows->count()" :sub="__('Products and variants')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Low stock')" icon="exclamation-triangle-fill" tone="warn" :value="$lowCount" :sub="__('At or below threshold')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Out of stock')" icon="x-octagon-fill" tone="bad" :value="$outCount" :sub="__('Needs restocking')" /></div>
    @if (\App\Support\BusinessProfile::hasModule('batch_expiry'))
        <div class="col-6 col-lg-3"><x-stat-card :label="__('Expiring in 60 days')" icon="hourglass-split" tone="vio" :value="$expiringSoon" :sub="__('Batches')" /></div>
    @endif
</div>

<ul class="nav nav-pills mb-3 gap-2">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#inv-stock" type="button">{{ __('Stock') }}</button></li>
    @if (\App\Support\BusinessProfile::hasModule('batch_expiry'))<li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#inv-batches" type="button">{{ __('Batches & expiry') }}</button></li>@endif
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#inv-moves" type="button">{{ __('History') }}</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="inv-stock"><div class="card"><div class="card-body">
        <table id="stockTable" class="table align-middle w-100">
            <thead><tr><th>{{ __('Item') }}</th><th>SKU</th><th>{{ __('In stock') }}</th><th>{{ __('Alert at') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
            <tbody>
            @foreach ($rows as $r)
                @php($low = $r['qty'] <= $r['product']->low_stock_threshold)
                <tr>
                    <td class="fw-semibold">{{ $r['product']->trans('name') }}@if ($r['variant']) <span class="text-muted">· {{ $r['variant']->name }}</span>@endif</td>
                    <td class="figure text-muted">{{ $r['sku'] ?: '—' }}</td>
                    <td class="figure fw-bold {{ $r['qty'] === 0 ? 'text-danger' : ($low ? 'text-warning' : '') }}">{{ $r['qty'] }}</td>
                    <td class="figure text-muted">{{ $r['product']->low_stock_threshold }}</td>
                    <td>@if ($r['qty'] === 0)<span class="badge rounded-pill text-bg-danger">{{ __('Out of stock') }}</span>@elseif ($low)<span class="badge rounded-pill text-bg-warning">{{ __('Low') }}</span>@else<span class="badge rounded-pill text-bg-success">{{ __('OK') }}</span>@endif</td>
                    <td class="text-end">@can('inventory.adjust')<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.inventory.adjust', ['item' => ($r['variant'] ? 'v:'.$r['variant']->id : 'p:'.$r['product']->id)]) }}"><i class="bi bi-arrow-left-right me-1"></i>{{ __('Adjust') }}</a>@endcan</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div></div>

    @if (\App\Support\BusinessProfile::hasModule('batch_expiry'))
    <div class="tab-pane fade" id="inv-batches"><div class="card"><div class="card-body">
        <table id="batchTable" class="table align-middle w-100">
            <thead><tr><th>{{ __('Item') }}</th><th>{{ __('Batch') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Expires') }}</th></tr></thead>
            <tbody>
            @foreach ($batches as $b)
                @php($days = $b->expires_at ? (int) now()->startOfDay()->diffInDays($b->expires_at, false) : null)
                <tr>
                    <td class="fw-semibold">{{ $b->product->name }}</td>
                    <td class="figure">{{ $b->batch_no }}</td>
                    <td class="figure">{{ $b->qty }}</td>
                    <td data-order="{{ $b->expires_at?->timestamp ?? PHP_INT_MAX }}">
                        @if ($b->expires_at)
                            <span class="figure">{{ $b->expires_at->format('d M Y') }}</span>
                            @if ($days < 0)<span class="badge rounded-pill text-bg-danger ms-1">{{ __('Expired') }}</span>
                            @elseif ($days <= 60)<span class="badge rounded-pill text-bg-warning ms-1">{{ __(':n days left', ['n' => $days]) }}</span>@endif
                        @else — @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div></div>
    @endif

    <div class="tab-pane fade" id="inv-moves"><div class="card"><div class="card-body">
        <table id="moveTable" class="table align-middle w-100">
            <thead><tr><th>{{ __('When') }}</th><th>{{ __('Item') }}</th><th>{{ __('Change') }}</th><th>{{ __('Balance') }}</th><th>{{ __('Reason') }}</th><th>{{ __('By') }}</th></tr></thead>
            <tbody>
            @foreach ($movements as $m)
                <tr>
                    <td class="text-nowrap small text-muted" data-order="{{ $m->created_at->timestamp }}">{{ $m->created_at->format('d M Y, H:i') }}</td>
                    <td>{{ $m->product?->name }}@if ($m->variant) <span class="text-muted">· {{ $m->variant->name }}</span>@endif @if ($m->note)<div class="small text-muted">{{ $m->note }}</div>@endif</td>
                    <td class="figure fw-bold {{ $m->change > 0 ? 'text-success' : 'text-danger' }}">{{ $m->change > 0 ? '+' : '' }}{{ $m->change }}</td>
                    <td class="figure">{{ $m->balance }}</td>
                    <td><span class="badge rounded-pill text-bg-secondary">{{ __(ucfirst($m->reason)) }}</span></td>
                    <td class="small text-muted">{{ $m->user?->name ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div></div>
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
    $(function () {
        var lang = { search: "_INPUT_", searchPlaceholder: @json(__('Search…')) };
        $('#stockTable').DataTable({ responsive: true, pageLength: 10, order: [[2, 'asc']], language: lang, columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { orderable: false, targets: -1 }] });
        if ($('#batchTable').length) $('#batchTable').DataTable({ responsive: true, pageLength: 10, order: [[3, 'asc']], language: lang });
        $('#moveTable').DataTable({ responsive: true, pageLength: 10, order: [[0, 'desc']], language: lang });
        // tabs hidden at load time need their columns re-measured when shown
        document.querySelectorAll('[data-bs-toggle="pill"]').forEach(function (b) {
            b.addEventListener('shown.bs.tab', function () { $.fn.dataTable.tables({ api: true }).columns.adjust().responsive.recalc(); });
        });
    });
</script>
@endpush
