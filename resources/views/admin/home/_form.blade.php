@php
    $editing = $section->exists;
    $type = $section->type;
    $st = fn (string $k, $d = '') => old($k, $section->settings[$k] ?? $d);
    $locales = collect(\App\Support\Locales::all())->except('en');
    $trVal = fn (string $loc, string $f) => old("tr.$loc.$f", $section->translations[$loc][$f] ?? '');
    $labels = ['eyebrow' => 'Small label', 'title' => 'Title', 'subtitle' => 'Subtitle', 'button_label' => 'Button text', 'button2_label' => 'Second button text', 'body' => 'Text'];
    $items = old('items', $section->settings['items'] ?? []);
@endphp
<form method="POST" action="{{ $editing ? route('admin.home-sections.update', $section) : route('admin.home-sections.store') }}" enctype="multipart/form-data">
    @csrf @if ($editing) @method('PUT') @endif
    <input type="hidden" name="type" value="{{ $type }}">

    <div class="row g-3">
        @if (in_array($type, ['hero', 'banner']))
            <div class="col-md-6"><label class="form-label">{{ __('Small label') }}</label><input name="eyebrow" value="{{ $st('eyebrow') }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label><input name="title" value="{{ $st('title') }}" required class="form-control @error('title') is-invalid @enderror">@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label">{{ __('Subtitle') }}</label><textarea name="subtitle" rows="2" class="form-control">{{ $st('subtitle') }}</textarea></div>
            <div class="col-md-6"><label class="form-label">{{ __('Button text') }}</label><input name="button_label" value="{{ $st('button_label') }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">{{ __('Button link') }}</label><input name="button_url" value="{{ $st('button_url', '/shop') }}" placeholder="/shop" class="form-control figure @error('button_url') is-invalid @enderror">@error('button_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @if ($type === 'hero')
                <div class="col-md-6"><label class="form-label">{{ __('Second button text') }}</label><input name="button2_label" value="{{ $st('button2_label') }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">{{ __('Second button link') }}</label><input name="button2_url" value="{{ $st('button2_url') }}" class="form-control figure @error('button2_url') is-invalid @enderror">@error('button2_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endif
            <div class="col-12">
                <label class="form-label">{{ __('Background image') }}</label>
                @if ($editing && $section->imageUrl())<div class="mb-2"><img src="{{ $section->imageUrl() }}" alt="" style="height:80px;border-radius:12px"> <div class="form-check d-inline-block ms-3"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="rmImg"><label class="form-check-label" for="rmImg">{{ __('Remove image') }}</label></div></div>@endif
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control @error('image') is-invalid @enderror">@error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @elseif ($type === 'categories')
            <div class="col-md-8"><label class="form-label">{{ __('Title') }}</label><input name="title" value="{{ $st('title', 'Shop by category') }}" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">{{ __('How many') }}</label><input type="number" min="1" max="24" name="limit" value="{{ $st('limit', 8) }}" class="form-control figure"></div>
            <div class="col-12">
                <label class="form-label">{{ __('Categories to show') }}</label>
                <select name="category_ids[]" multiple class="form-select" data-select style="width:100%">@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(in_array($c->id, (array) $st('category_ids', [])))>{{ $c->path }}</option>@endforeach</select>
                <div class="form-text">{{ __('Leave empty to show all top-level categories.') }}</div>
            </div>
        @elseif ($type === 'products')
            <div class="col-md-8"><label class="form-label">{{ __('Title') }}</label><input name="title" value="{{ $st('title', 'New arrivals') }}" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">{{ __('How many') }}</label><input type="number" min="1" max="24" name="limit" value="{{ $st('limit', 8) }}" class="form-control figure"></div>
            <div class="col-md-6"><label class="form-label">{{ __('Show') }}</label>
                <select name="source" id="prodSource" class="form-select">@foreach (['new' => 'Newest products', 'featured' => 'Featured products', 'category' => 'From one category'] as $k => $l)<option value="{{ $k }}" @selected($st('source', 'new') === $k)>{{ __($l) }}</option>@endforeach</select></div>
            <div class="col-md-6" id="prodCat"><label class="form-label">{{ __('Category') }}</label>
                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror"><option value="">—</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected((string) $st('category_id') === (string) $c->id)>{{ $c->path }}</option>@endforeach</select>@error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        @elseif ($type === 'text')
            <div class="col-12"><label class="form-label">{{ __('Title') }}</label><input name="title" value="{{ $st('title') }}" class="form-control"></div>
            <div class="col-12"><label class="form-label">{{ __('Text') }} <span class="text-danger">*</span></label><textarea name="body" rows="6" required class="form-control @error('body') is-invalid @enderror">{{ $st('body') }}</textarea>@error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        @elseif (isset(\App\Http\Controllers\Admin\HomeSectionController::ITEMS[$type]))
            @php [$maxItems, $itemFields] = \App\Http\Controllers\Admin\HomeSectionController::ITEMS[$type]; $itemLabels = ['icon' => 'Icon', 'title' => $type === 'testimonials' ? 'Name' : ($type === 'faq' ? 'Question' : 'Title'), 'role' => 'Role / city', 'text' => $type === 'testimonials' ? 'Quote' : ($type === 'faq' ? 'Answer' : 'Text')]; @endphp
            @if ($type !== 'features')
                <div class="col-md-6"><label class="form-label">{{ __('Heading') }}</label><input name="title" value="{{ $st('title') }}" maxlength="120" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">{{ __('Sub-heading') }}</label><input name="subtitle" value="{{ $st('subtitle') }}" maxlength="300" class="form-control"></div>
            @endif
            <div class="col-12 small text-muted">@if (in_array('icon', $itemFields)){{ __('Icon names come from Bootstrap Icons, e.g. truck, shield-check, headset, gift.') }} @endif{{ __('Up to :n items; an item with no title is skipped.', ['n' => $maxItems]) }}</div>
            @for ($i = 0; $i < $maxItems; $i++)
                <div class="col-12"><div class="row g-2 align-items-end border rounded-3 p-2" style="border-color:var(--border) !important">
                    @foreach ($itemFields as $f)
                        <div class="col-md-{{ $f === 'icon' ? 3 : ($f === 'text' ? 12 : 4) }}"><label class="form-label small">{{ __($itemLabels[$f]) }}</label>
                            @if ($f === 'text' && in_array($type, ['faq', 'testimonials']))<textarea name="items[{{ $i }}][{{ $f }}]" rows="2" maxlength="600" class="form-control">{{ $items[$i][$f] ?? '' }}</textarea>
                            @else<input name="items[{{ $i }}][{{ $f }}]" value="{{ $items[$i][$f] ?? '' }}" maxlength="240" class="form-control {{ $f === 'icon' ? 'figure' : '' }}" @if ($f === 'icon') placeholder="truck" @endif>@endif</div>
                    @endforeach
                    @foreach ($locales as $code => $m) @foreach (array_diff($itemFields, ['icon']) as $f)
                        <div class="col-md-6"><input name="tr[{{ $code }}][items][{{ $i }}][{{ $f }}]" dir="{{ $m['dir'] }}" value="{{ old("tr.$code.items.$i.$f", $section->translations[$code]['items'][$i][$f] ?? '') }}" placeholder="{{ __($itemLabels[$f]) }} · {{ $m['label'] }}" class="form-control form-control-sm"></div>
                    @endforeach @endforeach
                </div></div>
            @endfor
        @elseif ($type === 'blog')
            <div class="col-md-6"><label class="form-label">{{ __('Heading') }}</label><input name="title" value="{{ $st('title', 'From our health blog') }}" maxlength="120" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">{{ __('How many articles') }}</label><input type="number" min="1" max="6" name="limit" value="{{ $st('limit', 3) }}" class="form-control figure"></div>
            <div class="col-12"><label class="form-label">{{ __('Sub-heading') }}</label><input name="subtitle" value="{{ $st('subtitle') }}" maxlength="300" class="form-control"></div>
            <div class="col-12 small text-muted">{{ __('Shows your newest published articles from Health blog. Nothing shows until there is one.') }}</div>
        @elseif ($type === 'reviews')
            <div class="col-md-6"><label class="form-label">{{ __('Heading') }}</label><input name="title" value="{{ $st('title', 'What our customers say') }}" maxlength="120" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">{{ __('Sub-heading') }}</label><input name="subtitle" value="{{ $st('subtitle') }}" maxlength="300" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">{{ __('How many reviews') }}</label><input type="number" min="3" max="24" name="limit" value="{{ $st('limit', 9) }}" class="form-control figure"></div>
            <div class="col-md-4"><label class="form-label">{{ __('Show reviews rated at least') }}</label><select name="min_rating" class="form-select">@foreach ([5, 4, 3, 2, 1] as $r)<option value="{{ $r }}" @selected((int) $st('min_rating', 4) === $r)>{{ $r }} ★</option>@endforeach</select></div>
            <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="show_summary" value="1" id="revSum" @checked(old('show_summary', $section->settings['show_summary'] ?? true))><label class="form-check-label" for="revSum">{{ __('Show the rating summary') }}</label></div></div>
            <div class="col-12 small text-muted">{{ __('Real customer reviews appear here by themselves once they are approved under Reviews. Nothing shows until there is at least one.') }}</div>
        @elseif ($type === 'cta')
            <div class="col-md-6"><label class="form-label">{{ __('Small label') }}</label><input name="eyebrow" value="{{ $st('eyebrow') }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label><input name="title" value="{{ $st('title') }}" required maxlength="120" class="form-control"></div>
            <div class="col-12"><label class="form-label">{{ __('Subtitle') }}</label><textarea name="subtitle" rows="2" maxlength="300" class="form-control">{{ $st('subtitle') }}</textarea></div>
            <div class="col-md-6"><label class="form-label">{{ __('Button text') }}</label><input name="button_label" value="{{ $st('button_label') }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">{{ __('Button link') }}</label><input name="button_url" value="{{ $st('button_url', '/shop') }}" class="form-control figure @error('button_url') is-invalid @enderror">@error('button_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">{{ __('Second button text') }}</label><input name="button2_label" value="{{ $st('button2_label') }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">{{ __('Second button link') }}</label><input name="button2_url" value="{{ $st('button2_url') }}" class="form-control figure @error('button2_url') is-invalid @enderror">@error('button2_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label">{{ __('Background image') }}</label>
                @if ($editing && $section->imageUrl())<div class="mb-2"><img src="{{ $section->imageUrl() }}" alt="" style="height:80px;border-radius:12px"> <div class="form-check d-inline-block ms-3"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="rmImg2"><label class="form-check-label" for="rmImg2">{{ __('Remove image') }}</label></div></div>@endif
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control"></div>
        @endif

        <div class="col-12"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="is_active" value="1" id="secActive" @checked(old('is_active', $section->is_active ?? true))><label class="form-check-label" for="secActive">{{ __('Show on the home page') }}</label></div></div>
    </div>

    @if ($translatable)
        <div class="mt-4 pt-3" style="border-top:1px dashed var(--border)">
            <div class="small text-muted mb-2"><i class="bi bi-translate me-1"></i>{{ __('Translations (optional) — shown when a visitor picks that language.') }}</div>
            <div class="row g-3">
                @foreach ($locales as $code => $m) @foreach ($translatable as $f)
                    <div class="col-md-6"><label class="form-label small">{{ __($labels[$f]) }} · {{ $m['label'] }}</label>
                        @if ($f === 'body')<textarea name="tr[{{ $code }}][{{ $f }}]" rows="3" dir="{{ $m['dir'] }}" class="form-control">{{ $trVal($code, $f) }}</textarea>
                        @else<input name="tr[{{ $code }}][{{ $f }}]" dir="{{ $m['dir'] }}" value="{{ $trVal($code, $f) }}" class="form-control">@endif
                    </div>
                @endforeach @endforeach
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.home-sections.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
@if ($type === 'products')
@push('scripts')
<script nonce="{{ csp_nonce() }}">(function () { var s = document.getElementById('prodSource'), c = document.getElementById('prodCat'); function f() { c.style.display = s.value === 'category' ? '' : 'none'; } s.addEventListener('change', f); f(); })();</script>
@endpush
@endif
