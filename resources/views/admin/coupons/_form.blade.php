@php
    $editing = isset($coupon);
    $coupon = $coupon ?? null;
@endphp
<form method="POST" action="{{ $editing ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Code') }} <span class="text-danger">*</span></label>
            <input name="code" value="{{ old('code', $coupon->code ?? '') }}" required autofocus style="text-transform:uppercase" class="form-control figure @error('code') is-invalid @enderror" placeholder="EID20">@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Type') }}</label>
            <select name="type" id="cpType" class="form-select">@foreach (['percent' => 'Percentage off', 'fixed' => 'Fixed amount off', 'free_shipping' => 'Free shipping'] as $k => $l)<option value="{{ $k }}" @selected(old('type', $coupon->type ?? 'percent') === $k)>{{ __($l) }}</option>@endforeach</select></div>
        <div class="col-md-4 cp-value"><label class="form-label fw-medium">{{ __('Value') }}</label><input type="number" step="0.01" min="0" name="value" value="{{ old('value', $coupon->value ?? '') }}" class="form-control figure @error('value') is-invalid @enderror">@error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label fw-medium">{{ __('Minimum spend') }}</label><input type="number" step="0.01" min="0" name="min_subtotal" value="{{ old('min_subtotal', $coupon->min_subtotal ?? '') }}" class="form-control figure"></div>
        <div class="col-md-4 cp-cap"><label class="form-label fw-medium">{{ __('Max discount') }}</label><input type="number" step="0.01" min="0" name="max_discount" value="{{ old('max_discount', $coupon->max_discount ?? '') }}" class="form-control figure"></div>
        <div class="col-md-4"><label class="form-label fw-medium">{{ __('Starts') }}</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon?->starts_at?->format('Y-m-d\TH:i') ?? null) }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label fw-medium">{{ __('Ends') }}</label><input type="datetime-local" name="ends_at" value="{{ old('ends_at', $coupon?->ends_at?->format('Y-m-d\TH:i') ?? null) }}" class="form-control @error('ends_at') is-invalid @enderror">@error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label fw-medium">{{ __('Usage limit') }}</label><input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}" class="form-control figure" placeholder="∞"></div>
        <div class="col-12"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="is_active" value="1" id="cpActive" @checked(old('is_active', $coupon->is_active ?? true))><label class="form-check-label" for="cpActive">{{ __('Active') }}</label></div></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
@push('scripts')
<script>(function () { var t = document.getElementById('cpType'); function f() { document.querySelectorAll('.cp-value').forEach(function (e) { e.style.display = t.value === 'free_shipping' ? 'none' : ''; }); document.querySelectorAll('.cp-cap').forEach(function (e) { e.style.display = t.value === 'percent' ? '' : 'none'; }); } t.addEventListener('change', f); f(); })();</script>
@endpush
