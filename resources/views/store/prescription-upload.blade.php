@extends('layouts.store')
@php $page = 'prescription'; @endphp
@section('title', __('Upload your prescription'))
@section('description', __('Send a photo of your prescription and our pharmacist will call you back to confirm your medicines.'))

@section('content')
<div class="sf-container py-3">
    <nav class="sf-crumbs mk-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>›</span><span>{{ __('Upload your prescription') }}</span></nav>

    @if (session('rx_sent'))
        <div class="fx-rx-done mk-box">
            <span class="fx-rx-check"><i class="bi bi-check-lg"></i></span>
            <h1>{{ __('Thank you, :name!', ['name' => session('rx_sent')]) }}</h1>
            <p>{{ __('We received your prescription. A pharmacist will call you shortly to confirm your medicines and delivery.') }}</p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="{{ route('store.shop') }}" class="btn btn-primary btn-lg"><i class="bi bi-capsule me-1"></i> {{ __('Browse medicines') }}</a>
                <a href="{{ route('store.prescription.upload') }}" class="btn btn-outline-primary btn-lg">{{ __('Send another') }}</a>
            </div>
        </div>
    @else
    <div class="fx-rx">
        <div class="fx-rx-intro">
            <span class="mk-tag"><i class="bi bi-prescription2"></i> {{ __('No cart needed') }}</span>
            <h1>{{ __('Upload your prescription') }}</h1>
            <p>{{ __('Send a clear photo. Our licensed pharmacist checks it and calls you back to confirm the medicines, price and delivery.') }}</p>
            <ol class="fx-rx-steps">
                <li><span>1</span><div><b>{{ __('Upload') }}</b><small>{{ __('Photo or PDF of your prescription') }}</small></div></li>
                <li><span>2</span><div><b>{{ __('Pharmacist calls') }}</b><small>{{ __('We confirm medicines and total') }}</small></div></li>
                <li><span>3</span><div><b>{{ __('Delivered') }}</b><small>{{ __('To your door, pay on delivery') }}</small></div></li>
            </ol>
            <ul class="mk-sum-trust">
                <li><i class="bi bi-shield-lock"></i> {{ __('Private: only our pharmacists can see your files.') }}</li>
                <li><i class="bi bi-patch-check"></i> {{ __('Genuine medicines from licensed sources.') }}</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('store.prescription.send') }}" enctype="multipart/form-data" class="mk-box fx-rx-form" id="rxForm">
            @csrf
            <label class="fx-drop" id="rxDrop" for="rxFiles">
                <input id="rxFiles" type="file" name="files[]" accept="image/jpeg,image/png,image/webp,application/pdf" multiple required class="visually-hidden">
                <span class="fx-drop-ic"><i class="bi bi-cloud-arrow-up"></i></span>
                <b>{{ __('Drop your prescription here') }}</b>
                <small>{{ __('or tap to take a photo / choose files · JPG, PNG, PDF · up to 5 files, 5 MB each') }}</small>
                <span class="fx-drop-list" id="rxList" aria-live="polite"></span>
            </label>
            @error('files')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
            @error('files.*')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="rxName">{{ __('Full name') }}</label><input id="rxName" name="name" value="{{ old('name', $defaults['name']) }}" required maxlength="120" autocomplete="name" class="form-control @error('name') is-invalid @enderror">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="rxPhone">{{ __('Phone') }}</label><input id="rxPhone" name="phone" type="tel" value="{{ old('phone', $defaults['phone']) }}" required maxlength="30" autocomplete="tel" inputmode="tel" class="form-control @error('phone') is-invalid @enderror">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label class="form-label" for="rxCity">{{ __('City') }}</label><input id="rxCity" name="city" value="{{ old('city', $defaults['city']) }}" maxlength="120" autocomplete="address-level2" data-fx-city class="form-control @error('city') is-invalid @enderror"></div>
                <div class="col-md-6"><label class="form-label" for="rxMail">{{ __('Email (optional)') }}</label><input id="rxMail" name="email" type="email" value="{{ old('email', $defaults['email']) }}" maxlength="190" autocomplete="email" class="form-control @error('email') is-invalid @enderror">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><label class="form-label" for="rxNotes">{{ __('Anything we should know? (optional)') }}</label><textarea id="rxNotes" name="notes" rows="2" maxlength="500" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea></div>
            </div>
            <x-honeypot />
            <x-captcha />
            <button class="btn btn-primary btn-lg w-100 mt-3"><i class="bi bi-send me-1"></i> {{ __('Send prescription') }}</button>
        </form>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    var drop = document.getElementById('rxDrop'), input = document.getElementById('rxFiles'), list = document.getElementById('rxList');
    if (!drop || !input) return;
    function paint() {
        list.innerHTML = '';
        Array.prototype.forEach.call(input.files, function (f) {
            var chip = document.createElement('span'); chip.className = 'fx-chip';
            if (/^image\//.test(f.type)) { var im = document.createElement('img'); im.alt = ''; im.src = URL.createObjectURL(f); chip.appendChild(im); }
            else { var i = document.createElement('i'); i.className = 'bi bi-file-earmark-pdf'; chip.appendChild(i); }
            chip.appendChild(document.createTextNode(' ' + f.name)); list.appendChild(chip);
        });
        drop.classList.toggle('has-files', input.files.length > 0);
    }
    input.addEventListener('change', paint);
    ['dragenter', 'dragover'].forEach(function (e) { drop.addEventListener(e, function (ev) { ev.preventDefault(); drop.classList.add('drag'); }); });
    ['dragleave', 'drop'].forEach(function (e) { drop.addEventListener(e, function (ev) { ev.preventDefault(); drop.classList.remove('drag'); }); });
    drop.addEventListener('drop', function (ev) { if (ev.dataTransfer && ev.dataTransfer.files.length) { input.files = ev.dataTransfer.files; paint(); } });
})();
</script>
@endpush
