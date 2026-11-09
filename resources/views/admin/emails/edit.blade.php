@extends('layouts.admin')
@section('title', __('Email templates'))
@section('content')
<x-page-header :title="__('Email templates')" :eyebrow="__('Storefront')" :sub="__('Change the wording customers see in e-mails. Leave a box empty to keep the built-in text.')" />
<form method="POST" action="{{ route('admin.emails.update') }}" data-no-ajax>
    @csrf @method('PUT')
    <div class="card mb-3"><div class="card-body">
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="status_updates" value="1" id="stUp" @checked(old('status_updates', setting('emails.status_updates', true)))><label class="form-check-label" for="stUp"><b>{{ __('E-mail customers when their order is confirmed, on its way, ready, completed or cancelled') }}</b></label></div>
    </div></div>
    @foreach ($fields as $key => [$label, $placeholders])
        <div class="card mb-3"><div class="card-body">
            <h2 class="mb-1 h6">{{ __($label) }}</h2>
            @if ($placeholders)<div class="small text-muted mb-3">{{ __('You can use') }}: <code>{{ implode('  ', $placeholders) }}</code></div>@endif
            <div class="row g-2">
                @foreach ($locales as $code => $m)
                    <div class="col-12"><label class="form-label small mb-1">{{ $m['label'] }}</label>
                        <textarea name="t[{{ $key }}][{{ $code }}]" rows="2" maxlength="500" dir="{{ $m['dir'] }}" class="form-control">{{ old("t.$key.$code", setting('emails.'.$key.($code === 'en' ? '' : "_$code"))) }}</textarea></div>
                @endforeach
            </div>
        </div></div>
    @endforeach
    <div class="d-flex justify-content-end"><button class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button></div>
</form>
@endsection
