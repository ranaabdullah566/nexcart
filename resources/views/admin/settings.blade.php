@extends('layouts.admin')
@section('title', __('Store settings'))

@php
    $v = fn (string $key) => old($key, setting($key));
    $canSave = auth()->user()->can('settings.update');
    $fieldset = $canSave ? '' : 'disabled';
@endphp

@section('content')
<x-page-header :title="__('Store settings')" :eyebrow="__('Admin')" :sub="__('Customise your store, theme, languages and features.')" />

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf @method('PUT')
    <fieldset {{ $fieldset }}>
    <ul class="nav nav-pills mb-3 gap-2" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-store" type="button"><i class="bi bi-shop me-1"></i> {{ __('Store') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-look" type="button"><i class="bi bi-palette-fill me-1"></i> {{ __('Appearance') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-pay" type="button"><i class="bi bi-wallet2 me-1"></i> {{ __('Payments') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-modules" type="button"><i class="bi bi-puzzle-fill me-1"></i> {{ __('Modules') }}</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-store">
            <div class="card mb-3"><div class="card-body">
                <h5 class="mb-3">{{ __('Store information') }}</h5>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">{{ __('Store name') }}</label><input name="store[name]" value="{{ $v('store.name') }}" required class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('Tagline') }}</label><input name="store[tagline]" value="{{ $v('store.tagline') }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('Contact email') }}</label><input type="email" name="store[email]" value="{{ $v('store.email') }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">{{ __('Contact phone') }}</label><input name="store[phone]" value="{{ $v('store.phone') }}" class="form-control"></div>
                </div>
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h5 class="mb-3">{{ __('Languages') }}</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Default language') }}</label>
                        <select name="store[default_locale]" class="form-select">
                            @foreach ($locales as $code => $meta)<option value="{{ $code }}" @selected($v('store.default_locale') === $code)>{{ $meta['label'] }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label d-block">{{ __('Enabled languages') }}</label>
                        @foreach ($locales as $code => $meta)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="store[locales][]" value="{{ $code }}" id="loc-{{ $code }}" @checked(in_array($code, (array) $v('store.locales'), true))>
                                <label class="form-check-label" for="loc-{{ $code }}">{{ $meta['label'] }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div></div>

            <div class="card mb-3"><div class="card-body">
                <h5 class="mb-3">{{ __('Money & tax') }}</h5>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">{{ __('Currency code') }}</label><input name="store[currency]" value="{{ $v('store.currency') }}" maxlength="3" required class="form-control figure"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Currency symbol') }}</label><input name="store[currency_symbol]" value="{{ $v('store.currency_symbol') }}" maxlength="5" required class="form-control"></div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Timezone') }}</label>
                        <select name="store[timezone]" class="form-select" data-select>
                            @foreach (timezone_identifiers_list() as $tz)<option @selected($v('store.timezone') === $tz)>{{ $tz }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">{{ __('Tax rate (%)') }}</label><input type="number" step="0.01" min="0" max="100" name="store[tax_rate]" value="{{ $v('store.tax_rate') }}" class="form-control figure"></div>
                    <div class="col-md-9 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input type="hidden" name="store[tax_inclusive]" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="store[tax_inclusive]" value="1" id="taxInc" @checked((bool) $v('store.tax_inclusive'))>
                            <label class="form-check-label" for="taxInc">{{ __('Prices include tax') }}</label>
                        </div>
                    </div>
                </div>
            </div></div>
        </div>

        <div class="tab-pane fade" id="tab-look">
            <div class="card mb-3"><div class="card-body">
                <h5 class="mb-3">{{ __('Theme') }}</h5>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">{{ __('Primary colour') }}</label><input type="color" name="theme[primary]" value="{{ $v('theme.primary') }}" class="form-control form-control-color w-100"></div>
                    <div class="col-md-3"><label class="form-label">{{ __('Accent colour') }}</label><input type="color" name="theme[accent]" value="{{ $v('theme.accent') }}" class="form-control form-control-color w-100"></div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Default mode') }}</label>
                        <select name="theme[mode]" class="form-select">
                            @foreach (['dark' => 'Dark', 'light' => 'Light', 'auto' => 'Follow device'] as $k => $l)<option value="{{ $k }}" @selected($v('theme.mode') === $k)>{{ __($l) }}</option>@endforeach
                        </select>
                        <div class="form-text">{{ __('Visitors can still switch with the sun/moon button.') }}</div>
                    </div>
                </div>
            </div></div>
        </div>

        <div class="tab-pane fade" id="tab-pay">
            <div class="card mb-3"><div class="card-body">
                <h5 class="mb-1">{{ __('Payment methods') }}</h5>
                <p class="text-muted small mb-3">{{ __('Choose how customers can pay at checkout.') }}</p>
                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" role="switch" name="payments[cod]" value="1" id="payCod" @checked(old('payments.cod', setting('payments.cod', true)))><label class="form-check-label" for="payCod"><b>{{ __('Cash on delivery') }}</b> <span class="text-muted">— {{ __('pay when the order arrives or is picked up') }}</span></label></div>

                <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="payments[bank]" value="1" id="payBank" @checked(old('payments.bank', setting('payments.bank', false)))><label class="form-check-label" for="payBank"><b>{{ __('Bank transfer') }}</b></label></div>
                <textarea name="payments[bank_instructions]" rows="3" class="form-control mb-3 @error('payments.bank_instructions') is-invalid @enderror" placeholder="{{ __('Bank name, account title, IBAN …') }}">{{ old('payments.bank_instructions', setting('payments.bank_instructions')) }}</textarea>
                @error('payments.bank_instructions')<div class="text-danger small mb-3">{{ $message }}</div>@enderror

                <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="payments[stripe]" value="1" id="payStripe" @checked(old('payments.stripe', setting('payments.stripe', false)))><label class="form-check-label" for="payStripe"><b>{{ __('Card payments (Stripe)') }}</b></label></div>
                <input type="password" name="payments[stripe_secret]" autocomplete="off" class="form-control figure @error('payments.stripe_secret') is-invalid @enderror" placeholder="{{ $hasStripeKey ? '•••••••• '.__('saved — leave blank to keep') : 'sk_live_…' }}">
                @error('payments.stripe_secret')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <div class="form-text">{{ __('Your Stripe secret key is stored encrypted and never shown again.') }}</div>
            </div></div>
        </div>

        <div class="tab-pane fade" id="tab-modules">
            <div class="card mb-3"><div class="card-body">
                <h5 class="mb-1">{{ __('Feature modules') }}</h5>
                <p class="text-muted small mb-3">{{ __('Switch features on or off for this business. Defaults come from the business profile chosen at setup.') }}</p>
                <div class="row g-3">
                    @foreach ($modules as $key => $label)
                        <div class="col-md-6 col-lg-4">
                            <div class="form-check form-switch">
                                <input type="hidden" name="modules[{{ $key }}]" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" name="modules[{{ $key }}]" value="1" id="mod-{{ $key }}" @checked(\App\Support\BusinessProfile::hasModule($key))>
                                <label class="form-check-label" for="mod-{{ $key }}">{{ $label }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div></div>
        </div>
    </div>

    @if ($canSave)
        <div class="d-flex justify-content-end"><button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save settings') }}</button></div>
    @endif
    </fieldset>
</form>
@endsection
