{{-- date presets + optional filters; $route = route name, $routeParams = its parameters --}}
@php
    $presets = \App\Http\Controllers\Admin\ReportHubController::RANGES;
    $keep = collect(request()->except(['range', 'from', 'to', 'export', 'page']));
    $go = fn ($r) => route($route, ($routeParams ?? []) + $keep->all() + ['range' => $r]);
@endphp
<div class="rp-bar">
    <form method="GET" class="rp-range" data-no-ajax>
        @foreach ($keep as $k => $v)@if (is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
        <label class="visually-hidden" for="rpRange">{{ __('Period') }}</label>
        <select id="rpRange" name="range" class="form-select form-select-sm rp-select" onchange="if(this.value!=='custom'){this.form.submit()}else{this.form.querySelector('.rp-custom').hidden=false}">
            @foreach ($presets as $k => $l)<option value="{{ $k }}" @selected($key === (string) $k)>{{ __($l) }}</option>@endforeach
        </select>
        <span class="rp-custom" @if ($key !== 'custom') hidden @endif>
            <input type="date" name="from" aria-label="{{ __('From') }}" value="{{ local_time($from, 'Y-m-d') }}" class="form-control form-control-sm">
            <span class="text-muted">→</span>
            <input type="date" name="to" aria-label="{{ __('To') }}" value="{{ local_time($to, 'Y-m-d') }}" class="form-control form-control-sm">
            <button class="btn btn-sm btn-primary">{{ __('Apply') }}</button>
        </span>
    </form>
    <div class="rp-quick" role="group" aria-label="{{ __('Quick periods') }}">
        @foreach (['today', '7', '30', 'month', 'last_month', 'year'] as $k)<a class="rp-chip {{ $key === (string) $k ? 'on' : '' }}" href="{{ $go($k) }}" data-no-modal>{{ __($presets[$k]) }}</a>@endforeach
    </div>
    @if (! empty($filterable))
    <form method="GET" class="rp-filters" data-no-ajax>
        <input type="hidden" name="range" value="{{ $key }}">@if ($key === 'custom')<input type="hidden" name="from" value="{{ local_time($from, 'Y-m-d') }}"><input type="hidden" name="to" value="{{ local_time($to, 'Y-m-d') }}">@endif
        <select name="method" class="form-select form-select-sm" aria-label="{{ __('Payment method') }}" onchange="this.form.submit()"><option value="">{{ __('All payment methods') }}</option>@foreach ($methods as $m)<option value="{{ $m }}" @selected(($filters['method'] ?? '') === $m)>{{ ['cod' => __('Cash on delivery'), 'bank' => __('Bank transfer'), 'stripe' => __('Card (Stripe)')][$m] ?? ucfirst($m) }}</option>@endforeach</select>
        <select name="city" class="form-select form-select-sm" aria-label="{{ __('City') }}" onchange="this.form.submit()"><option value="">{{ __('All cities') }}</option>@foreach ($cities as $c)<option value="{{ $c }}" @selected(($filters['city'] ?? '') === $c)>{{ $c }}</option>@endforeach</select>
        @if (filled($filters['method'] ?? null) || filled($filters['city'] ?? null))<a class="btn btn-sm btn-outline-secondary" href="{{ route($route, ($routeParams ?? []) + ['range' => $key] + ($key === 'custom' ? ['from' => local_time($from, 'Y-m-d'), 'to' => local_time($to, 'Y-m-d')] : [])) }}" data-no-modal><i class="bi bi-x-lg"></i> {{ __('Clear filters') }}</a>@endif
    </form>
    @endif
</div>
