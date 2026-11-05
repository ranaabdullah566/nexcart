@php($editing = isset($staff))
<form method="POST" action="{{ $editing ? route('admin.staff.update', $staff) : route('admin.staff.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Name') }} <span class="text-danger">*</span></label>
            <input type="text" name="name" value="{{ old('name', $staff->name ?? '') }}" required autofocus class="form-control @error('name') is-invalid @enderror">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Email') }} <span class="text-danger">*</span></label>
            <input type="email" name="email" value="{{ old('email', $staff->email ?? '') }}" required class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label fw-medium">{{ __('Role') }} <span class="text-danger">*</span></label>
            <select name="role" required class="form-select @error('role') is-invalid @enderror">
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(old('role', $editing ? $staff->getRoleNames()->first() : 'staff') === $role)>{{ __(ucfirst($role)) }}</option>
                @endforeach
            </select>
            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Password') }} @unless ($editing)<span class="text-danger">*</span>@endunless</label>
            <input type="password" name="password" minlength="8" autocomplete="new-password" @required(! $editing) class="form-control @error('password') is-invalid @enderror">
            @if ($editing)<div class="form-text">{{ __('Leave blank to keep the current password.') }}</div>@endif
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Confirm password') }}</label>
            <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" class="form-control">
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
