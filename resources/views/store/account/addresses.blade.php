@extends('store.account.layout', ['active' => 'addresses', 'heading' => __('Addresses')])
@section('panel')
@php $v = fn ($k, $d = '') => old($k, $editing?->{$k} ?? $d); @endphp
<h1 class="ac-title">{{ __('Delivery addresses') }}</h1>
<div class="ac-addr-grid">
    @foreach ($addresses as $a)
        <article class="ac-addr @if ($a->is_default) is-default @endif">
            <header><b><i class="bi bi-geo-alt"></i> {{ $a->label }}</b>@if ($a->is_default)<span class="mk-status mk-status-ok">{{ __('Default') }}</span>@endif</header>
            <p>{{ $a->name }} · {{ $a->phone }}<br>{{ $a->oneLine() }}</p>
            <footer>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('store.account.addresses.edit', $a) }}">{{ __('Edit') }}</a>
                @unless ($a->is_default)<form method="POST" action="{{ route('store.account.addresses.default', $a) }}" data-no-validate>@csrf<button class="btn btn-sm btn-outline-secondary">{{ __('Make default') }}</button></form>@endunless
                <form method="POST" action="{{ route('store.account.addresses.destroy', $a) }}" data-no-validate onsubmit="return confirm(@js(__('Remove this address?')))">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" aria-label="{{ __('Remove') }}"><i class="bi bi-trash"></i></button></form>
            </footer>
        </article>
    @endforeach
</div>

<form method="POST" action="{{ $editing ? route('store.account.addresses.update', $editing) : route('store.account.addresses.store') }}" class="mk-box" id="addrForm">
    @csrf @if ($editing) @method('PUT') @endif
    <h2 class="mk-box-title"><i class="bi bi-plus-circle"></i> {{ $editing ? __('Edit address') : __('Add a new address') }}</h2>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="ad_label">{{ __('Label') }}</label><input id="ad_label" name="label" list="adLabels" value="{{ $v('label') }}" required maxlength="40" placeholder="{{ __('Home, Office…') }}" class="form-control"><datalist id="adLabels"><option value="{{ __('Home') }}"><option value="{{ __('Office') }}"></datalist></div>
        <div class="col-md-4"><label class="form-label" for="ad_name">{{ __('Full name') }}</label><input id="ad_name" name="name" value="{{ $v('name', $user->name) }}" required maxlength="120" class="form-control"></div>
        <div class="col-md-4"><label class="form-label" for="ad_phone">{{ __('Phone') }}</label><input id="ad_phone" name="phone" value="{{ $v('phone', $user->phone) }}" required maxlength="40" class="form-control"></div>
        <div class="col-12"><label class="form-label" for="ad_line">{{ __('Address') }}</label><input id="ad_line" name="address_line" value="{{ $v('address_line') }}" required maxlength="255" class="form-control"></div>
        <div class="col-md-5"><label class="form-label" for="ad_city">{{ __('City') }}</label><input id="ad_city" name="city" value="{{ $v('city') }}" required maxlength="120" class="form-control"></div>
        <div class="col-md-4"><label class="form-label" for="ad_area">{{ __('Area') }}</label><input id="ad_area" name="area" value="{{ $v('area') }}" maxlength="120" class="form-control"></div>
        <div class="col-md-3"><label class="form-label" for="ad_postal">{{ __('Postal code') }}</label><input id="ad_postal" name="postal_code" value="{{ $v('postal_code') }}" maxlength="20" class="form-control"></div>
        <div class="col-12"><div class="form-check"><input type="checkbox" class="form-check-input" name="is_default" value="1" id="ad_default" @checked(old('is_default', $editing?->is_default))><label class="form-check-label" for="ad_default">{{ __('Use as my default address') }}</label></div></div>
    </div>
    <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> {{ __('Save address') }}</button>
        @if ($editing)<a class="btn btn-outline-secondary" href="{{ route('store.account.addresses') }}">{{ __('Cancel') }}</a>@endif
    </div>
</form>
@endsection
