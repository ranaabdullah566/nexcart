@php($editing = isset($category))
<form method="POST" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Name') }} <span class="text-danger">*</span></label>
            <input name="name" value="{{ old('name', $category->name ?? '') }}" required autofocus class="form-control @error('name') is-invalid @enderror">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Parent category') }}</label>
            <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                <option value="">— {{ __('Top level') }} —</option>
                @foreach ($parents as $p)<option value="{{ $p->id }}" @selected((string) old('parent_id', $category->parent_id ?? ($preParent ?? '')) === (string) $p->id)>{{ $p->path }}</option>@endforeach
            </select>
            @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">{{ __('Up to 3 levels: Category › Sub-category › Sub-sub-category. Only categories that still have room are listed.') }}</div>
        </div>
        <div class="col-12">
            <label class="form-label fw-medium">{{ __('Description') }}</label>
            <textarea name="description" rows="3" class="form-control">{{ old('description', $category->description ?? '') }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-medium">{{ __('Order') }}</label>
            <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="form-control figure">
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch mb-2"><input type="checkbox" role="switch" class="form-check-input" name="is_active" value="1" id="catActive" @checked(old('is_active', $category->is_active ?? true))><label class="form-check-label" for="catActive">{{ __('Visible in store') }}</label></div>
        </div>
    </div>
    <div class="mt-3 pt-3" style="border-top:1px dashed var(--border)">
    <div class="small text-muted mb-2"><i class="bi bi-search me-1"></i>{{ __('Search engines (optional) — what Google shows for this page. Leave empty to use the name and description.') }}</div>
    <div class="row g-3">
        <div class="col-12"><label class="form-label">{{ __('Search title') }}</label><input name="meta_title" maxlength="120" value="{{ old('meta_title', $category->meta_title ?? '') }}" class="form-control"><div class="form-text">{{ __('Up to 120 characters; about 60 show in Google.') }}</div></div>
        <div class="col-12"><label class="form-label">{{ __('Search description') }}</label><textarea name="meta_description" rows="2" maxlength="300" class="form-control">{{ old('meta_description', $category->meta_description ?? '') }}</textarea><div class="form-text">{{ __('Up to 300 characters; about 155 show in Google.') }}</div></div>
    </div>
</div>
    @include('partials.translation-fields', ['model' => $category ?? null, 'fields' => ['name' => 'Name', 'description' => ['Description', 'textarea']]])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
