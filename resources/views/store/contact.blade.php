@extends('layouts.store')
@php
    $page = 'contact';
    $wa = filled(setting('store.whatsapp')) ? 'https://wa.me/'.preg_replace('/\D/', '', setting('store.whatsapp')) : null;
    $cards = array_filter([
        'phone' => setting('store.phone') ? ['telephone-fill', __('Call us'), setting('store.phone'), 'tel:'.preg_replace('/[^0-9+]/', '', setting('store.phone'))] : null,
        'wa' => $wa ? ['whatsapp', __('WhatsApp'), __('Chat with a pharmacist'), $wa] : null,
        'mail' => setting('store.email') ? ['envelope-fill', __('Email'), setting('store.email'), 'mailto:'.setting('store.email')] : null,
        'addr' => setting('store.address') ? ['geo-alt-fill', __('Visit us'), setting('store.address'), null] : null,
        'hours' => setting('store.hours') ? ['clock-fill', __('Opening hours'), setting('store.hours'), null] : null,
    ]);
@endphp
@section('title', __('Contact us'))
@section('description', __('Questions about your order or medicines? Send us a message and our team will get back to you.'))

@section('content')
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><span>{{ __('Contact us') }}</span></nav>

    @if (session('contact_sent'))
        <div class="fx-rx-done mk-box">
            <span class="fx-rx-check"><i class="bi bi-check-lg"></i></span>
            <h1>{{ __('Thank you, :name!', ['name' => session('contact_sent')]) }}</h1>
            <p>{{ __('We received your message and will reply to your email soon.') }}</p>
            <a href="{{ route('store.home') }}" class="btn btn-primary btn-lg"><i class="bi bi-house-door me-1"></i> {{ __('Back to home') }}</a>
        </div>
    @else
    <div class="fx-rx">
        <div class="fx-rx-intro">
            <span class="mk-tag"><i class="bi bi-chat-heart"></i> {{ __('We are here to help') }}</span>
            <h1>{{ __('Contact us') }}</h1>
            <p>{{ __('Questions about your order or medicines? Send us a message — no account needed.') }}</p>
            @if ($cards)
                <div class="fx-contact-cards">
                    @foreach ($cards as [$icon, $label, $value, $href])
                        @if ($href)<a href="{{ $href }}" class="fx-contact-card" @if ($icon === 'whatsapp') target="_blank" rel="noopener noreferrer" @endif><i class="bi bi-{{ $icon }}"></i><span><small>{{ $label }}</small><b dir="auto">{{ $value }}</b></span></a>
                        @else<div class="fx-contact-card"><i class="bi bi-{{ $icon }}"></i><span><small>{{ $label }}</small><b dir="auto">{{ $value }}</b></span></div>@endif
                    @endforeach
                </div>
            @endif
            <p class="fx-disclaimer"><i class="bi bi-exclamation-triangle"></i> {{ __('For a medical emergency, call your local emergency number or go to the nearest hospital.') }}</p>
        </div>

        <form method="POST" action="{{ route('store.contact.send') }}" class="mk-box fx-rx-form">
            @csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="ctName">{{ __('Full name') }} <span class="text-danger">*</span></label><input id="ctName" name="name" value="{{ old('name', $defaults['name']) }}" required maxlength="120" autocomplete="name" class="form-control @error('name') is-invalid @enderror">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="ctMail">{{ __('Email') }} <span class="text-danger">*</span></label><input id="ctMail" type="email" name="email" value="{{ old('email', $defaults['email']) }}" required maxlength="190" autocomplete="email" class="form-control @error('email') is-invalid @enderror">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="ctPhone">{{ __('Phone (optional)') }}</label><input id="ctPhone" type="tel" name="phone" value="{{ old('phone', $defaults['phone']) }}" maxlength="30" autocomplete="tel" class="form-control @error('phone') is-invalid @enderror">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="ctSub">{{ __('Subject (optional)') }}</label><input id="ctSub" name="subject" value="{{ old('subject') }}" maxlength="150" class="form-control"></div>
                <div class="col-12"><label class="form-label" for="ctMsg">{{ __('Message') }} <span class="text-danger">*</span></label><textarea id="ctMsg" name="message" rows="5" required minlength="10" maxlength="3000" class="form-control @error('message') is-invalid @enderror">{{ old('message') }}</textarea>@error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
            <x-honeypot />
            <x-captcha />
            <button class="btn btn-primary btn-lg w-100 mt-3"><i class="bi bi-send me-1"></i> {{ __('Send message') }}</button>
        </form>
    </div>
    @endif
</div>
@endsection
