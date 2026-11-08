@extends('layouts.wizard')
@section('title', __('Set up your store'))
@section('step', '2')
@section('headline', __('Let’s set up your store'))
@section('lead', __('Your online pharmacy comes preconfigured — prescriptions, batch & expiry, delivery slots and riders. You can change everything later from the admin panel.'))

@section('content')
<div class="mk-wz-head"><span class="mk-eyebrow">{{ __('Step :n of :t', ['n' => 2, 't' => 2]) }}</span><h1>{{ __('Set up your store') }}</h1></div>

<form method="POST" action="{{ route('setup.store') }}">
    @csrf

    <section class="mk-wz-card">
        <h2 class="mk-box-title"><i class="bi bi-shop-window"></i> {{ __('Store') }}</h2>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="stName">{{ __('Store name') }} <span class="text-danger">*</span></label><input id="stName" name="store_name" value="{{ old('store_name') }}" required maxlength="120" class="form-control @error('store_name') is-invalid @enderror">@error('store_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="stTz">{{ __('Timezone') }} <span class="text-danger">*</span></label><input id="stTz" name="timezone" value="{{ old('timezone', 'Asia/Karachi') }}" required class="form-control @error('timezone') is-invalid @enderror">@error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="stCur">{{ __('Currency code') }} <span class="text-danger">*</span></label><input id="stCur" name="currency" value="{{ old('currency', 'PKR') }}" maxlength="3" minlength="3" required class="form-control figure @error('currency') is-invalid @enderror">@error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="stSym">{{ __('Currency symbol') }} <span class="text-danger">*</span></label><input id="stSym" name="currency_symbol" value="{{ old('currency_symbol', 'Rs') }}" maxlength="5" required class="form-control @error('currency_symbol') is-invalid @enderror">@error('currency_symbol')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
    </section>

    <section class="mk-wz-card">
        <h2 class="mk-box-title"><i class="bi bi-person-badge"></i> {{ __('Owner account') }}</h2>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="owName">{{ __('Your name') }} <span class="text-danger">*</span></label><input id="owName" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name" class="form-control @error('name') is-invalid @enderror">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="owMail">{{ __('Email') }} <span class="text-danger">*</span></label><input id="owMail" type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="username" class="form-control @error('email') is-invalid @enderror">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="owPass">{{ __('Password') }} <span class="text-danger">*</span></label>
                <div class="mk-field"><i class="bi bi-lock"></i><input id="owPass" type="password" name="password" minlength="10" required autocomplete="new-password" data-strength="#pwRules" class="form-control @error('password') is-invalid @enderror"><button type="button" class="mk-eye" data-pw-toggle aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button></div>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="owPass2">{{ __('Confirm password') }} <span class="text-danger">*</span></label>
                <div class="mk-field"><i class="bi bi-shield-lock"></i><input id="owPass2" type="password" name="password_confirmation" minlength="10" required autocomplete="new-password" class="form-control"><button type="button" class="mk-eye" data-pw-toggle aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button></div></div>
            <div class="col-12"><ul class="mk-rules mb-0" id="pwRules" aria-live="polite">
                <li data-rule="len"><i class="bi bi-circle"></i> {{ __('At least 10 characters') }}</li>
                <li data-rule="letter"><i class="bi bi-circle"></i> {{ __('A letter') }}</li>
                <li data-rule="digit"><i class="bi bi-circle"></i> {{ __('A number') }}</li>
            </ul></div>
        </div>
    </section>

    <label class="mk-sample"><input type="checkbox" name="sample_data" value="1" @checked(old('sample_data'))><span><b>{{ __('Add sample products') }}</b><small>{{ __('a ready-made catalogue to look around with. You can remove it any time.') }}</small></span></label>

    <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-rocket-takeoff me-1"></i> {{ __('Launch my store') }}</button>
</form>
@endsection
