@php
    $editing = isset($zone);
    $zone = $zone ?? null;
    $rows = old('rates', $editing ? $zone->rates->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'price' => $r->price, 'free_over' => $r->free_over, 'min_subtotal' => $r->min_subtotal, 'eta' => $r->eta, 'eta_minutes' => $r->eta_minutes, 'is_pickup' => $r->is_pickup])->all() : [['name' => 'Standard delivery', 'price' => 0]]);
@endphp
<form method="POST" action="{{ $editing ? route('admin.shipping.update', $zone) : route('admin.shipping.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Zone name') }} <span class="text-danger">*</span></label><input name="name" value="{{ old('name', $zone->name ?? '') }}" required autofocus class="form-control @error('name') is-invalid @enderror" placeholder="Karachi">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch mb-2"><input type="checkbox" role="switch" class="form-check-input" name="is_active" value="1" id="zActive" @checked(old('is_active', $zone->is_active ?? true))><label class="form-check-label" for="zActive">{{ __('Active') }}</label></div></div>
        <div class="col-12"><label class="form-label fw-medium">{{ __('Cities') }}</label><textarea name="cities" rows="2" class="form-control" placeholder="Karachi, Lahore">{{ old('cities', implode(', ', $zone->cities ?? [])) }}</textarea>
            <div class="form-text">{{ __('Comma or line separated. Leave empty for the “all other cities” zone.') }}</div></div>
    </div>

    <h6 class="mt-4 mb-2">{{ __('Delivery options') }}</h6>
    @error('rates')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
    <div id="rateRows" class="d-grid gap-2">
        @foreach ($rows as $i => $r)
            <div class="border rounded-3 p-2 rate-row" style="border-color:var(--border) !important">
                <input type="hidden" name="rates[{{ $i }}][id]" value="{{ $r['id'] ?? '' }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4"><label class="form-label small mb-1">{{ __('Name') }}</label><input name="rates[{{ $i }}][name]" value="{{ $r['name'] ?? '' }}" required class="form-control form-control-sm"></div>
                    <div class="col-6 col-md-2"><label class="form-label small mb-1">{{ __('Price') }}</label><input type="number" step="0.01" min="0" name="rates[{{ $i }}][price]" value="{{ $r['price'] ?? 0 }}" class="form-control form-control-sm figure"></div>
                    <div class="col-6 col-md-3"><label class="form-label small mb-1">{{ __('Free over') }}</label><input type="number" step="0.01" min="0" name="rates[{{ $i }}][free_over]" value="{{ $r['free_over'] ?? '' }}" class="form-control form-control-sm figure"></div>
                    <div class="col-6 col-md-3"><label class="form-label small mb-1">{{ __('Minimum order') }}</label><input type="number" step="0.01" min="0" name="rates[{{ $i }}][min_subtotal]" value="{{ $r['min_subtotal'] ?? '' }}" class="form-control form-control-sm figure"></div>
                    <div class="col-6 col-md-3"><label class="form-label small mb-1">{{ __('Delivery time') }}</label><input name="rates[{{ $i }}][eta]" value="{{ $r['eta'] ?? '' }}" class="form-control form-control-sm" placeholder="30–45 min"></div>
                    <div class="col-6 col-md-2"><label class="form-label small mb-1" title="{{ __('Used for the estimated arrival time') }}">{{ __('ETA (min)') }}</label><input type="number" min="1" name="rates[{{ $i }}][eta_minutes]" value="{{ $r['eta_minutes'] ?? '' }}" class="form-control form-control-sm figure" placeholder="40"></div>
                    <div class="col-8 col-md-5"><div class="form-check form-switch"><input type="hidden" name="rates[{{ $i }}][is_pickup]" value="0"><input type="checkbox" role="switch" class="form-check-input" name="rates[{{ $i }}][is_pickup]" value="1" id="pk{{ $i }}" @checked($r['is_pickup'] ?? false)><label class="form-check-label small" for="pk{{ $i }}">{{ __('Store pickup (no address needed)') }}</label></div></div>
                    <div class="col-4 col-md-2 text-end"><button type="button" class="nx-icon-btn rate-del" title="{{ __('Remove') }}"><i class="bi bi-trash3"></i></button></div>
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="rateAdd"><i class="bi bi-plus-lg me-1"></i> {{ __('Add option') }}</button>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.shipping.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
@push('scripts')
<script>
    (function () {
        var box = document.getElementById('rateRows'), next = box.children.length + 100;
        document.getElementById('rateAdd').addEventListener('click', function () {
            var i = next++, d = document.createElement('div');
            d.className = 'border rounded-3 p-2 rate-row'; d.style.borderColor = 'var(--border)';
            d.innerHTML = '<input type="hidden" name="rates[' + i + '][id]" value=""><div class="row g-2 align-items-end">' +
                '<div class="col-md-4"><label class="form-label small mb-1">' + @json(__('Name')) + '</label><input name="rates[' + i + '][name]" required class="form-control form-control-sm"></div>' +
                '<div class="col-6 col-md-2"><label class="form-label small mb-1">' + @json(__('Price')) + '</label><input type="number" step="0.01" min="0" name="rates[' + i + '][price]" value="0" class="form-control form-control-sm figure"></div>' +
                '<div class="col-6 col-md-3"><label class="form-label small mb-1">' + @json(__('Free over')) + '</label><input type="number" step="0.01" min="0" name="rates[' + i + '][free_over]" class="form-control form-control-sm figure"></div>' +
                '<div class="col-6 col-md-3"><label class="form-label small mb-1">' + @json(__('Minimum order')) + '</label><input type="number" step="0.01" min="0" name="rates[' + i + '][min_subtotal]" class="form-control form-control-sm figure"></div>' +
                '<div class="col-6 col-md-3"><label class="form-label small mb-1">' + @json(__('Delivery time')) + '</label><input name="rates[' + i + '][eta]" class="form-control form-control-sm"></div>' +
                '<div class="col-6 col-md-2"><label class="form-label small mb-1">' + @json(__('ETA (min)')) + '</label><input type="number" min="1" name="rates[' + i + '][eta_minutes]" class="form-control form-control-sm figure"></div>' +
                '<div class="col-8 col-md-5"><div class="form-check form-switch"><input type="hidden" name="rates[' + i + '][is_pickup]" value="0"><input type="checkbox" role="switch" class="form-check-input" name="rates[' + i + '][is_pickup]" value="1" id="pk' + i + '"><label class="form-check-label small" for="pk' + i + '">' + @json(__('Store pickup (no address needed)')) + '</label></div></div>' +
                '<div class="col-4 col-md-2 text-end"><button type="button" class="nx-icon-btn rate-del"><i class="bi bi-trash3"></i></button></div></div>';
            box.appendChild(d);
        });
        box.addEventListener('click', function (e) { var b = e.target.closest('.rate-del'); if (b && box.children.length > 1) b.closest('.rate-row').remove(); });
    })();
</script>
@endpush
