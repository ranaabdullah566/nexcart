@php
    $editing = isset($page);
    $page = $page ?? null;
@endphp
<form method="POST" action="{{ $editing ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-12"><label class="form-label fw-medium">{{ __('Title') }} <span class="text-danger">*</span></label><input name="title" value="{{ old('title', $page->title ?? '') }}" required autofocus class="form-control @error('title') is-invalid @enderror">@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12"><label class="form-label fw-medium">{{ __('Text') }}</label><textarea name="body" rows="9" class="form-control">{{ old('body', $page->body ?? '') }}</textarea><div class="form-text">{{ __('Plain text. A blank line starts a new paragraph.') }}</div></div>
        <div class="col-12"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="is_published" value="1" id="pgPub" @checked(old('is_published', $page->is_published ?? true))><label class="form-check-label" for="pgPub">{{ __('Published') }}</label></div></div>
    </div>
    <div class="mt-3 pt-3" style="border-top:1px dashed var(--border)">
        <div class="small text-muted mb-2"><i class="bi bi-search me-1"></i>{{ __('Search engines (optional) — what Google shows for this page. Leave empty to use the name and description.') }}</div>
        <div class="row g-3">
            <div class="col-12"><label class="form-label">{{ __('Search title') }}</label><input name="meta_title" maxlength="120" value="{{ old('meta_title', $page->meta_title ?? '') }}" class="form-control"></div>
            <div class="col-12"><label class="form-label">{{ __('Search description') }}</label><textarea name="meta_description" rows="2" maxlength="300" class="form-control">{{ old('meta_description', $page->meta_description ?? '') }}</textarea></div>
        </div>
    </div>
    @include('partials.translation-fields', ['model' => $page, 'fields' => ['title' => 'Title', 'body' => ['Text', 'textarea']]])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
