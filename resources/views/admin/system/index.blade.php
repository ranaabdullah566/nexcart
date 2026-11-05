@extends('layouts.admin')
@section('title', __('System health'))

@php($bad = collect($checks)->where('status', 'bad')->count())
@php($warn = collect($checks)->where('status', 'warn')->count())
@section('content')
<x-page-header :title="__('System health')" :eyebrow="__('System')" :sub="__('Is everything your store needs switched on and working?')" />

<div class="row g-3 mb-4 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Version')" icon="tag-fill" :value="$version" :sub="'PHP '.$stats['php'].' · Laravel '.$stats['laravel']" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Problems')" icon="x-octagon-fill" :tone="$bad ? 'bad' : 'ok'" :value="$bad" :sub="__('Need fixing')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Warnings')" icon="exclamation-triangle-fill" :tone="$warn ? 'warn' : 'ok'" :value="$warn" :sub="__('Worth a look')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('License')" icon="patch-check-fill" :tone="in_array($license['state'], ['valid', 'disabled']) ? 'ok' : 'warn'" :value="ucfirst($license['state'] === 'disabled' ? __('Not required') : $license['state'])" :sub="$license['data']['licensee'] ?? __('Environment').': '.$stats['env']" /></div>
</div>

@if ($update && $update['newer'])
    <div class="alert alert-info d-flex align-items-start gap-2"><i class="bi bi-cloud-arrow-down-fill fs-5"></i>
        <div><b>{{ __('NexCart :v is available', ['v' => $update['version']]) }}</b> ({{ __('you have :c', ['c' => $version]) }})@if ($update['notes'])<div class="small">{{ $update['notes'] }}</div>@endif
            <div class="small mt-1">{{ __('Back up, replace the application files with the new release, then run') }} <code>php artisan nexcart:update</code>. @if ($update['url'])<a href="{{ $update['url'] }}" target="_blank" rel="noopener" data-no-modal>{{ __('Download') }}</a>@endif</div></div></div>
@elseif ($updateConfigured)
    <div class="small text-muted mb-3"><i class="bi bi-check-circle me-1"></i>{{ $update ? __('You are on the latest version.') : __('Could not reach the update server.') }} <a href="{{ route('admin.system.index', ['check' => 1]) }}" data-no-modal>{{ __('Check again') }}</a></div>
@endif

<div class="card mb-3"><div class="card-body">
    <h5 class="mb-3">{{ __('Checks') }}</h5>
    @foreach ($checks as $c)
        <div class="d-flex gap-3 py-2 border-bottom align-items-start" style="border-color:var(--border) !important">
            <i class="bi bi-{{ ['ok' => 'check-circle-fill text-success', 'warn' => 'exclamation-triangle-fill text-warning', 'bad' => 'x-octagon-fill text-danger'][$c['status']] }} mt-1"></i>
            <div class="flex-grow-1"><div class="fw-semibold">{{ $c['label'] }}</div>@if ($c['hint'] && $c['status'] !== 'ok')<div class="small text-muted">{{ $c['hint'] }}</div>@endif</div>
            <div class="text-end small figure text-muted" style="max-width:45%;word-break:break-word">{{ $c['detail'] }}</div>
        </div>
    @endforeach
</div></div>

<div class="card"><div class="card-body">
    <h5 class="mb-1"><i class="bi bi-box-seam me-1"></i>{{ __('Sample data') }}</h5>
    <p class="text-muted small">{{ __('A ready-made catalogue for your business type (:type) with pictures — handy for a demo or to see how the store looks full.', ['type' => __(config('business_profiles.'.$businessType.'.label', $businessType))]) }}</p>
    @if ($demo)
        <form method="POST" action="{{ route('admin.system.demo.remove') }}" data-confirm="{{ __('Remove all sample products? Your own products are not touched.') }}" data-no-ajax>@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash3 me-1"></i> {{ __('Remove sample data') }}</button></form>
    @else
        <form method="POST" action="{{ route('admin.system.demo.load') }}" data-no-ajax>@csrf<button class="btn btn-primary"><i class="bi bi-magic me-1"></i> {{ __('Add sample products') }}</button></form>
    @endif
</div></div>
@endsection
