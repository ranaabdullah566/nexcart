@extends('layouts.guest')
@section('title', __('Set up your store'))

@section('content')
<div class="mb-4">
    <div class="nx-eyebrow">NexCart</div>
    <h1 class="nx-title">{{ __('Let’s set up your store') }}</h1>
    <p class="nx-sub">{{ __('Pick your business type — modules, checkout and theme are preconfigured. You can change everything later from the admin panel.') }}</p>
</div>

<form method="POST" action="{{ route('setup.store') }}">
    @csrf

    <div class="card mb-3"><div class="card-body">
        <h5 class="mb-3">{{ __('1 · Business type') }}</h5>
        <div class="row g-3">
            @foreach ($profiles as $key => $profile)
                <div class="col-sm-6">
                    <label class="nx-type">
                        <input type="radio" name="business_type" value="{{ $key }}" @checked(old('business_type', 'general') === $key)>
                        <span class="nx-type-body"><b class="d-block mb-1">{{ __($profile['label']) }}</b><small class="text-muted">{{ __($profile['description']) }}</small></span>
                    </label>
                </div>
            @endforeach
        </div>
        @error('business_type')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
    </div></div>

    <div class="card mb-3"><div class="card-body">
        <h5 class="mb-3">{{ __('2 · Store') }}</h5>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">{{ __('Store name') }}</label><input name="store_name" value="{{ old('store_name') }}" required class="form-control @error('store_name') is-invalid @enderror">@error('store_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">{{ __('Timezone') }}</label><input name="timezone" value="{{ old('timezone', 'Asia/Karachi') }}" required class="form-control @error('timezone') is-invalid @enderror">@error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">{{ __('Currency code') }}</label><input name="currency" value="{{ old('currency', 'PKR') }}" maxlength="3" required class="form-control figure @error('currency') is-invalid @enderror">@error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">{{ __('Currency symbol') }}</label><input name="currency_symbol" value="{{ old('currency_symbol', 'Rs') }}" maxlength="5" required class="form-control @error('currency_symbol') is-invalid @enderror">@error('currency_symbol')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
    </div></div>

    <div class="card mb-4"><div class="card-body">
        <h5 class="mb-3">{{ __('3 · Owner account') }}</h5>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">{{ __('Your name') }}</label><input name="name" value="{{ old('name') }}" required class="form-control @error('name') is-invalid @enderror">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">{{ __('Email') }}</label><input type="email" name="email" value="{{ old('email') }}" required class="form-control @error('email') is-invalid @enderror">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">{{ __('Password') }}</label><input type="password" name="password" minlength="10" required autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">{{ __('Confirm password') }}</label><input type="password" name="password_confirmation" minlength="10" required autocomplete="new-password" class="form-control"></div>
        </div>
    </div></div>

    <div class="form-check form-switch mb-4"><input class="form-check-input" type="checkbox" role="switch" name="sample_data" value="1" id="sampleData" @checked(old('sample_data'))><label class="form-check-label" for="sampleData"><b>{{ __('Add sample products') }}</b> <span class="text-muted">— {{ __('a ready-made catalogue to look around with. You can remove it any time.') }}</span></label></div>

    <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-rocket-takeoff me-1"></i> {{ __('Launch my store') }}</button>
</form>
@endsection
