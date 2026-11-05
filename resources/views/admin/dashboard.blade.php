@extends('layouts.admin')
@section('title', __('Dashboard'))

@section('content')
<x-page-header :title="__('Welcome back, :name', ['name' => explode(' ', auth()->user()->name)[0]])" :eyebrow="__('Overview')" :sub="__('Your store at a glance.')" />

<div class="row g-3 mb-4 stagger">
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Business type')" icon="shop" :value="__($profile['label'])" :sub="__('Chosen at setup')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Staff')" icon="person-badge-fill" tone="vio" :value="$staffCount" :sub="__('Active team members')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Modules on')" icon="puzzle-fill" tone="ok" :value="$modules->count()" :sub="__('Feature modules enabled')" /></div>
    <div class="col-6 col-lg-3"><x-stat-card :label="__('Languages')" icon="translate" tone="warn" :value="count($languages)" :sub="collect($languages)->pluck('label')->implode(' · ')" /></div>
</div>

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
                <li><i class="bi bi-circle me-2 text-muted"></i>{{ __('Add categories and products') }}</li>
                <li><i class="bi bi-circle me-2 text-muted"></i>{{ __('Open your storefront') }}</li>
                <li><i class="bi bi-circle me-2 text-muted"></i>{{ __('Take your first order') }}</li>
            </ul>
        </div></div>
    </div>
</div>
@endsection
