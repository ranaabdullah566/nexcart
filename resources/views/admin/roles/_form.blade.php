@php($editing = $role->exists)
<form method="POST" action="{{ $editing ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    @unless ($editing)
        <div class="mb-3">
            <label class="form-label fw-medium">{{ __('Role name') }} <span class="text-danger">*</span></label>
            <input name="name" value="{{ old('name') }}" required maxlength="40" class="form-control @error('name') is-invalid @enderror" placeholder="cashier">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endunless
    @if ($editing && $role->name === 'owner')
        <div class="nx-form-alert mb-3"><i class="bi bi-info-circle-fill"></i><div>{{ __('The owner role always has every permission.') }}</div></div>
    @endif
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 nx-perm-table">
            <thead><tr><th>{{ __('Area') }}</th><th>{{ __('Allowed actions') }}</th></tr></thead>
            <tbody>
            @foreach ($groups as $g => $info)
                <tr>
                    <td class="fw-semibold">{{ __($info['label']) }}</td>
                    <td>
                        @foreach ($info['actions'] as $a)
                            @php($name = "{$g}.{$a}")
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $name }}" id="p-{{ $g }}-{{ $a }}"
                                    @checked(in_array($name, old('permissions', $granted), true) || ($editing && $role->name === 'owner')) @disabled($editing && $role->name === 'owner')>
                                <label class="form-check-label" for="p-{{ $g }}-{{ $a }}">{{ __(ucfirst($a)) }}</label>
                            </div>
                        @endforeach
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary px-4">{{ __('Cancel') }}</a>
        @unless ($editing && $role->name === 'owner')<button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>@endunless
    </div>
</form>
