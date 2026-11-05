@php($editing = isset($attribute))
<form method="POST" action="{{ $editing ? route('admin.attributes.update', $attribute) : route('admin.attributes.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Label') }} <span class="text-danger">*</span></label>
            <input name="label" value="{{ old('label', $attribute->label ?? '') }}" required autofocus class="form-control @error('label') is-invalid @enderror" placeholder="Composition">
            @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Key') }} <span class="text-danger">*</span></label>
            <input name="key" value="{{ old('key', $attribute->key ?? '') }}" required pattern="[a-z][a-z0-9_]{1,63}" class="form-control figure @error('key') is-invalid @enderror" placeholder="composition" {{ $editing ? 'readonly' : '' }}>
            <div class="form-text">{{ __('Lowercase letters, numbers and underscores.') }}</div>
            @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Type') }}</label>
            <select name="type" id="attrType" class="form-select">
                @foreach (\App\Models\CatalogAttribute::TYPES as $t)<option value="{{ $t }}" @selected(old('type', $attribute->type ?? 'text') === $t)>{{ __(ucfirst($t)) }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Order') }}</label>
            <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $attribute->sort_order ?? 0) }}" class="form-control figure">
        </div>
        <div class="col-12" id="attrOptions">
            <label class="form-label fw-medium">{{ __('Choices (one per line)') }}</label>
            <textarea name="options" rows="4" class="form-control @error('options') is-invalid @enderror">{{ old('options', implode("\n", $attribute->options ?? [])) }}</textarea>
            @error('options')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label fw-medium d-block">{{ __('Show for these businesses') }}</label>
            @php($picked = old('business_types', $attribute->business_types ?? []))
            @foreach ($profiles as $key => $profile)
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="business_types[]" value="{{ $key }}" id="bt-{{ $key }}" @checked(in_array($key, (array) $picked, true))><label class="form-check-label" for="bt-{{ $key }}">{{ __($profile['label']) }}</label></div>
            @endforeach
            <div class="form-text">{{ __('Leave all unticked to show it for every business.') }}</div>
        </div>
        <div class="col-md-6"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="is_required" value="1" id="attrReq" @checked(old('is_required', $attribute->is_required ?? false))><label class="form-check-label" for="attrReq">{{ __('Required') }}</label></div></div>
        <div class="col-md-6"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="is_filterable" value="1" id="attrFil" @checked(old('is_filterable', $attribute->is_filterable ?? false))><label class="form-check-label" for="attrFil">{{ __('Use as a storefront filter') }}</label></div></div>
    </div>
    @include('partials.translation-fields', ['model' => $attribute ?? null, 'fields' => ['label' => 'Label']])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
@push('scripts')
<script nonce="{{ csp_nonce() }}">
    (function () {
        var t = document.getElementById('attrType'), o = document.getElementById('attrOptions');
        function sync() { o.style.display = t.value === 'select' ? '' : 'none'; }
        t.addEventListener('change', sync); sync();
    })();
</script>
@endpush
