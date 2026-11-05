@php($editing = $item->exists)
<form method="POST" action="{{ $editing ? route('admin.menu-items.update', $item) : route('admin.menu-items.store') }}">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Where') }}</label><select name="location" class="form-select">@foreach (['header' => 'Header', 'footer' => 'Footer'] as $k => $l)<option value="{{ $k }}" @selected(old('location', $item->location) === $k)>{{ __($l) }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label fw-medium">{{ __('Text') }} <span class="text-danger">*</span></label><input name="label" value="{{ old('label', $item->label) }}" required autofocus class="form-control @error('label') is-invalid @enderror">@error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label fw-medium">{{ __('Links to') }}</label><select name="type" id="miType" class="form-select">@foreach (['url' => 'A web address', 'page' => 'A page', 'category' => 'A category'] as $k => $l)<option value="{{ $k }}" @selected(old('type', $item->type) === $k)>{{ __($l) }}</option>@endforeach</select></div>
        <div class="col-md-8 mi-url"><label class="form-label fw-medium">{{ __('Address') }}</label><input name="url" value="{{ old('url', $item->url) }}" class="form-control figure @error('url') is-invalid @enderror" placeholder="/shop or https://…">@error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-8 mi-page"><label class="form-label fw-medium">{{ __('Page') }}</label><select name="page_id" class="form-select @error('page_id') is-invalid @enderror"><option value="">—</option>@foreach ($pages as $p)<option value="{{ $p->id }}" @selected((string) old('page_id', $item->page_id) === (string) $p->id)>{{ $p->title }}</option>@endforeach</select>@error('page_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-8 mi-category"><label class="form-label fw-medium">{{ __('Category') }}</label><select name="category_id" class="form-select @error('category_id') is-invalid @enderror"><option value="">—</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected((string) old('category_id', $item->category_id) === (string) $c->id)>{{ $c->path }}</option>@endforeach</select>@error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="is_active" value="1" id="miOn" @checked(old('is_active', $item->is_active ?? true))><label class="form-check-label" for="miOn">{{ __('Show in the store') }}</label></div></div>
    </div>
    @include('partials.translation-fields', ['model' => $item, 'fields' => ['label' => 'Text']])
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.menu-items.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
@push('scripts')
<script nonce="{{ csp_nonce() }}">(function () { var t = document.getElementById('miType'); function f() { ['url', 'page', 'category'].forEach(function (k) { document.querySelectorAll('.mi-' + k).forEach(function (e) { e.style.display = t.value === k ? '' : 'none'; }); }); } t.addEventListener('change', f); f(); })();</script>
@endpush
