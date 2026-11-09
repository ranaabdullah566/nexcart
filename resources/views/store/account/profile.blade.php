@extends('store.account.layout', ['active' => 'profile', 'heading' => __('Profile & password')])
@section('panel')
<h1 class="ac-title">{{ __('Profile & password') }}</h1>
<form method="POST" action="{{ route('store.account.update') }}" class="mk-box">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-12"><label class="form-label" for="ac_name">{{ __('Full name') }}</label><input id="ac_name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" class="form-control"></div>
        <div class="col-md-7"><label class="form-label" for="ac_email">{{ __('Email') }}</label><input id="ac_email" type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="190" class="form-control"></div>
        <div class="col-md-5"><label class="form-label" for="ac_phone">{{ __('Phone') }}</label><input id="ac_phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="40" class="form-control"></div>
    </div>
    <details class="mk-pw" @if ($errors->has('current_password') || $errors->has('password')) open @endif>
        <summary><i class="bi bi-key"></i> {{ __('Change email or password') }}<i class="bi bi-chevron-down"></i></summary>
        <div class="row g-3 mt-0">
            <div class="col-12"><label class="form-label" for="ac_current_password">{{ __('Current password') }}</label><input id="ac_current_password" type="password" name="current_password" maxlength="200" autocomplete="current-password" class="form-control @error('current_password') is-invalid @enderror"><div class="form-text">{{ __('Needed only to change your email or password.') }}</div></div>
            <div class="col-md-6"><label class="form-label" for="ac_password">{{ __('New password') }}</label><input id="ac_password" type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password" class="form-control"><div class="form-text">{{ __('Leave blank to keep the current password.') }}</div></div>
            <div class="col-md-6"><label class="form-label" for="ac_password_confirmation">{{ __('Confirm password') }}</label><input id="ac_password_confirmation" type="password" name="password_confirmation" maxlength="200" autocomplete="new-password" class="form-control"></div>
        </div>
    </details>
    <button class="btn btn-primary btn-lg w-100 mt-3"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
</form>
<form method="POST" action="{{ route('logout') }}" class="ac-signout-m mb-3" data-no-validate>@csrf<button class="btn btn-outline-secondary w-100"><i class="bi bi-box-arrow-right"></i> {{ __('Sign out') }}</button></form>
<p class="small text-muted">{{ __('Your delivery addresses are managed under Addresses.') }}</p>
@endsection
