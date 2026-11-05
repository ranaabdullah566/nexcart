@extends('layouts.admin')
@section('title', __('License'))

@php($d = $license['data'])
@section('content')
<x-page-header :title="__('License')" :eyebrow="__('System')" :sub="__('Your NexCart license key.')" />

@unless ($enabled)
    <div class="card"><div class="card-body"><i class="bi bi-patch-check-fill text-success me-2"></i>{{ __('License keys are not required for this copy of NexCart.') }}</div></div>
@else
    <div class="row g-3">
        <div class="col-lg-6"><div class="card h-100"><div class="card-body">
            <h5 class="mb-3">{{ __('Status') }}</h5>
            @php($ok = $license['state'] === 'valid')
            <div class="d-flex align-items-center gap-2 mb-3"><span class="badge rounded-pill text-bg-{{ $ok ? 'success' : ($license['state'] === 'grace' ? 'warning' : 'danger') }} px-3 py-2">{{ ['valid' => __('Licensed'), 'grace' => __('Expired — grace period'), 'expired' => __('Expired'), 'missing' => __('No license'), 'invalid' => __('Invalid key'), 'domain' => __('Wrong domain')][$license['state']] }}</span></div>
            @if ($license['reason'])<p class="text-muted">{{ $license['reason'] }}</p>@endif
            @if ($d)
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">{{ __('Licensed to') }}</dt><dd class="col-7">{{ $d['licensee'] ?? '—' }}</dd>
                    <dt class="col-5 text-muted">{{ __('Domain') }}</dt><dd class="col-7 figure">{{ $d['domain'] ?? '*' }}</dd>
                    <dt class="col-5 text-muted">{{ __('Edition') }}</dt><dd class="col-7">{{ ucfirst($d['edition'] ?? 'standard') }}</dd>
                    <dt class="col-5 text-muted">{{ __('Valid until') }}</dt><dd class="col-7">{{ ! empty($d['expires']) ? \Illuminate\Support\Carbon::parse($d['expires'])->format('d M Y') : __('Lifetime') }}</dd>
                    <dt class="col-5 text-muted">{{ __('License ID') }}</dt><dd class="col-7 figure">{{ $d['id'] ?? '—' }}</dd>
                </dl>
                <form method="POST" action="{{ route('admin.license.remove') }}" data-confirm="{{ __('Remove the license key from this store?') }}" data-no-ajax class="mt-3">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('Remove license') }}</button></form>
            @endif
        </div></div></div>
        <div class="col-lg-6"><div class="card h-100"><div class="card-body">
            <h5 class="mb-3">{{ $d ? __('Replace key') : __('Enter your license key') }}</h5>
            <form method="POST" action="{{ route('admin.license.activate') }}" data-no-ajax>
                @csrf
                <textarea name="key" rows="5" required class="form-control figure @error('key') is-invalid @enderror" placeholder="NXC.eyJ…">{{ old('key') }}</textarea>@error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <button class="btn btn-primary mt-3"><i class="bi bi-key me-1"></i> {{ __('Activate') }}</button>
            </form>
            <p class="small text-muted mt-3 mb-0">{{ __('Without a valid license the store keeps working; the admin shows a reminder and the storefront footer says “Powered by NexCart”.') }}</p>
        </div></div></div>
    </div>
@endunless
@endsection
