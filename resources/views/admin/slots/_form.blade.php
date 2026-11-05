@php
    $editing = isset($slot);
    $slot = $slot ?? null;
@endphp
<form method="POST" action="{{ $editing ? route('admin.delivery-slots.update', $slot) : route('admin.delivery-slots.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-12"><label class="form-label fw-medium">{{ __('Name') }} <span class="text-danger">*</span></label><input name="label" value="{{ old('label', $slot->label ?? '') }}" required autofocus class="form-control @error('label') is-invalid @enderror" placeholder="Morning">@error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label fw-medium">{{ __('From') }}</label><input type="time" name="starts_at" value="{{ old('starts_at', $slot->starts_at ?? '09:00') }}" required class="form-control figure @error('starts_at') is-invalid @enderror">@error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label fw-medium">{{ __('To') }}</label><input type="time" name="ends_at" value="{{ old('ends_at', $slot->ends_at ?? '12:00') }}" required class="form-control figure @error('ends_at') is-invalid @enderror">@error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label fw-medium">{{ __('Orders per day') }}</label><input type="number" min="1" name="capacity" value="{{ old('capacity', $slot->capacity ?? '') }}" class="form-control figure" placeholder="∞"></div>
        <div class="col-12"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="is_active" value="1" id="slActive" @checked(old('is_active', $slot->is_active ?? true))><label class="form-check-label" for="slActive">{{ __('Available to customers') }}</label></div></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.delivery-slots.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
