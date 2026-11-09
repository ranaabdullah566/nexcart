@extends('layouts.admin')
@section('title', __('Shipping'))

@section('content')
<x-page-header :title="__('Shipping')" :eyebrow="__('Sales')" :sub="__('Zones decide which delivery options a customer sees, based on their city.')">
    <x-slot:actions><a href="{{ route('admin.shipping.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New zone') }}</a></x-slot:actions>
</x-page-header>

<div class="d-grid gap-3">
@forelse ($zones as $z)
    <div class="card" style="{{ $z->is_active ? '' : 'opacity:.6' }}"><div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div><h2 class="mb-1 h5">{{ $z->name }} @unless ($z->is_active)<span class="badge text-bg-secondary ms-1">{{ __('Hidden') }}</span>@endunless</h2>
                <div class="small text-muted">{{ $z->cities ? implode(', ', $z->cities) : __('All other cities') }}</div></div>
            <x-row-actions :edit="route('admin.shipping.edit', $z)" :delete="route('admin.shipping.destroy', $z)" :confirm="__('Delete this zone and its rates?')" />
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            @foreach ($z->rates as $r)
                <span class="badge rounded-pill text-bg-secondary px-3 py-2 fw-normal">@if ($r->is_pickup)<i class="bi bi-shop me-1"></i>@endif<b>{{ $r->name }}</b> · {{ $r->is_pickup ? __('Free') : money($r->price) }}@if ($r->free_over) <span class="opacity-75">({{ __('free over :amount', ['amount' => money($r->free_over)]) }})</span>@endif @if ($r->eta)<span class="opacity-75"> · {{ $r->eta }}</span>@endif</span>
            @endforeach
        </div>
    </div></div>
@empty
    <div class="card"><div class="card-body text-center text-muted py-5">{{ __('No shipping zones yet. Add one so customers can check out.') }}</div></div>
@endforelse
</div>
@endsection
