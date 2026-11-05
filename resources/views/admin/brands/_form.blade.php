@php($editing = isset($brand))
<form method="POST" action="{{ $editing ? route('admin.brands.update', $brand) : route('admin.brands.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label fw-medium">{{ __('Name') }} <span class="text-danger">*</span></label>
            <input name="name" value="{{ old('name', $brand->name ?? '') }}" required autofocus class="form-control @error('name') is-invalid @enderror">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check form-switch mb-2"><input type="checkbox" role="switch" class="form-check-input" name="is_active" value="1" id="brandActive" @checked(old('is_active', $brand->is_active ?? true))><label class="form-check-label" for="brandActive">{{ __('Visible in store') }}</label></div>
        </div>
    </div>
    @include('partials.translation-fields', ['model' => $brand ?? null, 'fields' => ['name' => 'Name']])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
