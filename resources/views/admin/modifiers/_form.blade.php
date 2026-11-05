@php
    $editing = isset($group);
    $rows = old('options', $editing ? $group->options->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'price_delta' => $o->price_delta, 'is_default' => $o->is_default])->all() : [['name' => '', 'price_delta' => 0]]);
@endphp
<form method="POST" action="{{ $editing ? route('admin.modifier-groups.update', $group) : route('admin.modifier-groups.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Group name') }} <span class="text-danger">*</span></label>
            <input name="name" value="{{ old('name', $group->name ?? '') }}" required autofocus class="form-control @error('name') is-invalid @enderror" placeholder="Choose size">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3"><label class="form-label fw-medium">{{ __('Min choices') }}</label><input type="number" min="0" max="20" name="min_select" value="{{ old('min_select', $group->min_select ?? 0) }}" class="form-control figure"></div>
        <div class="col-md-3">
            <label class="form-label fw-medium">{{ __('Max choices') }}</label>
            <input type="number" min="1" max="20" name="max_select" value="{{ old('max_select', $group->max_select ?? 1) }}" class="form-control figure @error('max_select') is-invalid @enderror">
            @error('max_select')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="is_active" value="1" id="mgActive" @checked(old('is_active', $group->is_active ?? true))><label class="form-check-label" for="mgActive">{{ __('Active') }}</label></div></div>
    </div>

    <h6 class="mt-4 mb-2">{{ __('Options') }}</h6>
    @error('options')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
    <div id="optRows" class="d-grid gap-2">
        @foreach ($rows as $i => $r)
            <div class="row g-2 align-items-center opt-row">
                <input type="hidden" name="options[{{ $i }}][id]" value="{{ $r['id'] ?? '' }}">
                <div class="col-7"><input name="options[{{ $i }}][name]" value="{{ $r['name'] ?? '' }}" required placeholder="{{ __('Option name') }}" class="form-control"></div>
                <div class="col-3"><input type="number" step="0.01" name="options[{{ $i }}][price_delta]" value="{{ $r['price_delta'] ?? 0 }}" class="form-control figure" title="{{ __('Extra price') }}"></div>
                <div class="col-2 d-flex gap-1 justify-content-end">
                    <button type="button" class="nx-icon-btn opt-del" title="{{ __('Remove') }}"><i class="bi bi-trash3"></i></button>
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="optAdd"><i class="bi bi-plus-lg me-1"></i> {{ __('Add option') }}</button>

    @include('partials.translation-fields', ['model' => $group ?? null, 'fields' => ['name' => 'Group name']])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.modifier-groups.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
@push('scripts')
<script>
    (function () {
        var box = document.getElementById('optRows'), next = box.children.length + 100;
        document.getElementById('optAdd').addEventListener('click', function () {
            var i = next++, row = document.createElement('div');
            row.className = 'row g-2 align-items-center opt-row';
            row.innerHTML = '<input type="hidden" name="options[' + i + '][id]" value="">' +
                '<div class="col-7"><input name="options[' + i + '][name]" required placeholder="' + @json(__('Option name')) + '" class="form-control"></div>' +
                '<div class="col-3"><input type="number" step="0.01" name="options[' + i + '][price_delta]" value="0" class="form-control figure"></div>' +
                '<div class="col-2 d-flex justify-content-end"><button type="button" class="nx-icon-btn opt-del"><i class="bi bi-trash3"></i></button></div>';
            box.appendChild(row); row.querySelector('input[name$="[name]"]').focus();
        });
        box.addEventListener('click', function (e) {
            var b = e.target.closest('.opt-del'); if (!b) return;
            if (box.children.length > 1) b.closest('.opt-row').remove();
        });
    })();
</script>
@endpush
