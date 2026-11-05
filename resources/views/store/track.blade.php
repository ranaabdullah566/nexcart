@extends('layouts.store')
@php($page = 'track')
@section('title', __('Track an order'))

@section('content')
<div class="sf-container py-5" style="max-width:520px">
    <div class="text-center mb-4"><i class="bi bi-geo-alt fs-1" style="color:var(--acc-1)"></i><h1 class="h2 mt-2">{{ __('Track an order') }}</h1><p class="text-muted">{{ __('Enter your order number and the email you used at checkout.') }}</p></div>
    <form method="POST" action="{{ route('store.track.find') }}" class="sf-summary">
        @csrf
        <div class="mb-3"><label class="form-label">{{ __('Order number') }}</label><input name="number" value="{{ old('number') }}" required class="form-control figure @error('number') is-invalid @enderror" placeholder="BUR-100001">@error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label class="form-label">{{ __('Email') }}</label><input type="email" name="email" value="{{ old('email') }}" required class="form-control"></div>
        <button class="btn btn-primary w-100">{{ __('Find my order') }}</button>
    </form>
</div>
@endsection
