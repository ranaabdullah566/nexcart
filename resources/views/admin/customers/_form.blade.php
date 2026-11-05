@php($editing = $customer->exists)
@php($addr = $customer->default_address ?? [])
<form method="POST" action="{{ $editing ? route('admin.customers.update', $customer) : route('admin.customers.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Name') }} <span class="text-danger">*</span></label><input name="name" value="{{ old('name', $customer->name) }}" required maxlength="120" class="form-control"></div>
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Email') }} <span class="text-danger">*</span></label><input type="email" name="email" value="{{ old('email', $customer->email) }}" required maxlength="190" class="form-control"></div>
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Phone') }}</label><input name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="40" pattern="[0-9+\-()\s]{6,}" class="form-control" data-msg-pattern="{{ __('Enter a valid phone number.') }}"></div>
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('City') }}</label><input name="city" value="{{ old('city', $addr['city'] ?? '') }}" maxlength="120" class="form-control"></div>
        <div class="col-12"><label class="form-label fw-medium">{{ __('Address') }}</label><input name="address_line" value="{{ old('address_line', $addr['address_line'] ?? '') }}" maxlength="255" class="form-control"></div>
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Area') }}</label><input name="area" value="{{ old('area', $addr['area'] ?? '') }}" maxlength="120" class="form-control"></div>
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Postal code') }}</label><input name="postal_code" value="{{ old('postal_code', $addr['postal_code'] ?? '') }}" maxlength="20" class="form-control"></div>
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Password') }} @unless ($editing)<span class="text-danger">*</span>@endunless</label><input type="password" name="password" minlength="10" autocomplete="new-password" @required(! $editing) class="form-control">@if ($editing)<div class="form-text">{{ __('Leave blank to keep the current password.') }}</div>@else<div class="form-text">{{ __('At least 10 characters, with letters and numbers.') }}</div>@endif</div>
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Confirm password') }}</label><input type="password" name="password_confirmation" autocomplete="new-password" class="form-control"></div>
    </div>
    @if ($editing && isset($orders) && $orders->isNotEmpty())
        <h6 class="mt-4 mb-2 text-uppercase small text-muted">{{ __('Recent orders') }}</h6>
        @foreach ($orders as $o)<div class="d-flex justify-content-between small py-1 border-bottom"><a href="{{ route('admin.orders.show', $o) }}">{{ $o->number }}</a><span>{{ $o->statusLabel() }} · <b class="figure">{{ money($o->total) }}</b></span></div>@endforeach
    @endif
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary px-4">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
