@php
    $editing = $product->exists;
    $v = fn (string $f, $d = null) => old($f, $product->{$f} ?? $d);
    $type = old('type', $product->type ?? 'simple');
    $cur = setting('store.currency_symbol');
    $variantRows = old('variants', $editing ? $product->variants->map(fn ($x) => [
        'id' => $x->id, 'name' => $x->name, 'sku' => $x->sku, 'price' => $x->price, 'compare_price' => $x->compare_price,
        'stock_qty' => $x->stock_qty, 'is_active' => $x->is_active,
    ])->all() : []);
    $pickedGroups = collect(old('modifier_groups', $editing ? $product->modifierGroups->pluck('id')->all() : []))->map(fn ($i) => (int) $i)->all();
@endphp

<form method="POST" action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data">
    @csrf @if ($editing) @method('PUT') @endif

    <ul class="nav nav-pills mb-3 gap-2 flex-wrap" role="tablist">
        <li class="nav-item"><button class="nav-link active" type="button" data-bs-toggle="pill" data-bs-target="#pt-general"><i class="bi bi-card-text me-1"></i> {{ __('General') }}</button></li>
        <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#pt-price"><i class="bi bi-cash-coin me-1"></i> {{ __('Pricing & stock') }}</button></li>
        <li class="nav-item" id="tabVariantsLi"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#pt-variants"><i class="bi bi-layers-fill me-1"></i> {{ __('Variants') }}</button></li>
        @if ($attributes->isNotEmpty())<li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#pt-specs"><i class="bi bi-sliders2 me-1"></i> {{ __('Details') }}</button></li>@endif
        @if ($modifierGroups->isNotEmpty())<li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#pt-mods"><i class="bi bi-plus-slash-minus me-1"></i> {{ __('Add-ons') }}</button></li>@endif
        <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#pt-images"><i class="bi bi-images me-1"></i> {{ __('Images') }}</button></li>
        <li class="nav-item"><button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#pt-tr"><i class="bi bi-translate me-1"></i> {{ __('Translations') }}</button></li>
    </ul>

    <div class="tab-content">
        {{-- GENERAL --}}
        <div class="tab-pane fade show active" id="pt-general"><div class="card mb-3"><div class="card-body"><div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-medium">{{ __('Name') }} <span class="text-danger">*</span></label>
                <input name="name" value="{{ $v('name') }}" required class="form-control @error('name') is-invalid @enderror">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label fw-medium">{{ __('Status') }}</label>
                <select name="status" class="form-select">
                    @foreach (\App\Models\Product::STATUSES as $s)<option value="{{ $s }}" @selected($v('status', 'draft') === $s)>{{ __(ucfirst($s)) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-medium">{{ __('Category') }}</label>
                <select name="category_id" class="form-select" data-select>
                    <option value="">— {{ __('None') }} —</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected((string) $v('category_id') === (string) $c->id)>{{ $c->path }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-medium">{{ __('Brand') }}</label>
                <select name="brand_id" class="form-select" data-select>
                    <option value="">— {{ __('None') }} —</option>
                    @foreach ($brands as $b)<option value="{{ $b->id }}" @selected((string) $v('brand_id') === (string) $b->id)>{{ $b->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-medium">{{ __('Short description') }}</label>
                <input name="short_description" maxlength="300" value="{{ $v('short_description') }}" class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label fw-medium">{{ __('Description') }}</label>
                <textarea name="description" rows="6" class="form-control">{{ $v('description') }}</textarea>
            </div>
            <div class="col-md-4"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="is_featured" value="1" id="pFeat" @checked(old('is_featured', $product->is_featured))><label class="form-check-label" for="pFeat">{{ __('Featured') }}</label></div></div>
            @if ($showRx)
                <div class="col-md-8"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" name="requires_prescription" value="1" id="pRx" @checked(old('requires_prescription', $product->requires_prescription))><label class="form-check-label" for="pRx">{{ __('Requires a prescription') }}</label></div></div>
            @endif
        </div></div></div></div>

        {{-- PRICING & STOCK --}}
        <div class="tab-pane fade" id="pt-price"><div class="card mb-3"><div class="card-body"><div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-medium">{{ __('Product type') }}</label>
                <select name="type" id="pType" class="form-select @error('type') is-invalid @enderror">
                    <option value="simple" @selected($type === 'simple')>{{ __('Simple (one price)') }}</option>
                    <option value="variable" @selected($type === 'variable')>{{ __('Variable (sizes, colours …)') }}</option>
                </select>
                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4"><label class="form-label fw-medium">{{ __('SKU') }}</label><input name="sku" value="{{ $v('sku') }}" class="form-control figure @error('sku') is-invalid @enderror">@error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label fw-medium">{{ __('Cost') }} ({{ $cur }})</label><input type="number" step="0.01" min="0" name="cost" value="{{ $v('cost') }}" class="form-control figure"></div>

            <div class="col-md-4 simple-only"><label class="form-label fw-medium">{{ __('Price') }} ({{ $cur }}) <span class="text-danger">*</span></label><input type="number" step="0.01" min="0" name="price" value="{{ $type === 'simple' ? $v('price') : '' }}" class="form-control figure @error('price') is-invalid @enderror">@error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4 simple-only"><label class="form-label fw-medium">{{ __('Compare-at price') }} ({{ $cur }})</label><input type="number" step="0.01" min="0" name="compare_price" value="{{ $v('compare_price') }}" class="form-control figure"><div class="form-text">{{ __('Shown crossed out as the old price.') }}</div></div>

            <div class="col-12"><hr class="my-1"></div>
            <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch mb-2"><input type="checkbox" role="switch" class="form-check-input" name="track_stock" value="1" id="pTrack" @checked(old('track_stock', $product->track_stock ?? true))><label class="form-check-label" for="pTrack">{{ __('Track stock') }}</label></div></div>
            <div class="col-md-4 simple-only">
                @if ($editing)
                    <label class="form-label fw-medium">{{ __('In stock') }}</label>
                    <div class="input-group"><input class="form-control figure" value="{{ $product->stock_qty }}" disabled>
                        @can('inventory.adjust')<a class="btn btn-outline-secondary" href="{{ route('admin.inventory.adjust', ['item' => 'p:'.$product->id]) }}"><i class="bi bi-arrow-left-right me-1"></i>{{ __('Adjust') }}</a>@endcan</div>
                    <div class="form-text">{{ __('Change stock from Inventory so every movement is logged.') }}</div>
                @else
                    <label class="form-label fw-medium">{{ __('Opening stock') }}</label>
                    <input type="number" min="0" name="stock_qty" value="{{ old('stock_qty', 0) }}" class="form-control figure">
                @endif
            </div>
            <div class="col-md-4"><label class="form-label fw-medium">{{ __('Low-stock alert at') }}</label><input type="number" min="0" name="low_stock_threshold" value="{{ $v('low_stock_threshold', 5) }}" class="form-control figure"></div>
        </div></div></div></div>

        {{-- VARIANTS --}}
        <div class="tab-pane fade" id="pt-variants"><div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div><h5 class="mb-0">{{ __('Variants') }}</h5><div class="small text-muted">{{ __('Each variant has its own price, SKU and stock — e.g. "Large / Red".') }}</div></div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="varAdd"><i class="bi bi-plus-lg me-1"></i> {{ __('Add variant') }}</button>
            </div>
            @error('variants')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
            <div class="table-responsive"><table class="table align-middle mb-0" id="varTable">
                <thead><tr><th>{{ __('Name') }}</th><th>SKU</th><th>{{ __('Price') }}</th><th>{{ __('Compare-at') }}</th><th>{{ $editing ? __('Stock') : __('Opening stock') }}</th><th>{{ __('Active') }}</th><th></th></tr></thead>
                <tbody id="varRows">
                @foreach ($variantRows as $i => $r)
                    <tr class="var-row">
                        <td><input type="hidden" name="variants[{{ $i }}][id]" value="{{ $r['id'] ?? '' }}"><input name="variants[{{ $i }}][name]" value="{{ $r['name'] ?? '' }}" class="form-control" style="min-width:140px"></td>
                        <td><input name="variants[{{ $i }}][sku]" value="{{ $r['sku'] ?? '' }}" class="form-control figure" style="min-width:110px"></td>
                        <td><input type="number" step="0.01" min="0" name="variants[{{ $i }}][price]" value="{{ $r['price'] ?? '' }}" class="form-control figure" style="min-width:100px"></td>
                        <td><input type="number" step="0.01" min="0" name="variants[{{ $i }}][compare_price]" value="{{ $r['compare_price'] ?? '' }}" class="form-control figure" style="min-width:100px"></td>
                        <td>@if (! empty($r['id']))<input class="form-control figure" value="{{ $r['stock_qty'] ?? 0 }}" disabled style="min-width:80px">@else<input type="number" min="0" name="variants[{{ $i }}][stock_qty]" value="{{ $r['stock_qty'] ?? 0 }}" class="form-control figure" style="min-width:80px">@endif</td>
                        <td><input type="hidden" name="variants[{{ $i }}][is_active]" value="0"><input type="checkbox" class="form-check-input" name="variants[{{ $i }}][is_active]" value="1" @checked($r['is_active'] ?? true)></td>
                        <td class="text-end">
                            @if (! empty($r['id']) && auth()->user()->can('inventory.adjust'))<a class="nx-icon-btn d-inline-grid" href="{{ route('admin.inventory.adjust', ['item' => 'v:'.$r['id']]) }}" title="{{ __('Adjust stock') }}"><i class="bi bi-arrow-left-right"></i></a>@endif
                            <button type="button" class="nx-icon-btn var-del" title="{{ __('Remove') }}"><i class="bi bi-trash3"></i></button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div></div></div>

        {{-- DETAILS (admin-defined fields) --}}
        @if ($attributes->isNotEmpty())
        <div class="tab-pane fade" id="pt-specs"><div class="card mb-3"><div class="card-body"><div class="row g-3">
            @foreach ($attributes as $a)
                @php($val = old("specs.{$a->key}", $product->specs[$a->key] ?? ''))
                <div class="col-md-6">
                    <label class="form-label fw-medium">{{ $a->labelText() }} @if ($a->is_required && $a->type !== 'boolean')<span class="text-danger">*</span>@endif</label>
                    @if ($a->type === 'select')
                        <select name="specs[{{ $a->key }}]" class="form-select @error("specs.{$a->key}") is-invalid @enderror"><option value="">—</option>@foreach ($a->options ?? [] as $o)<option @selected((string) $val === (string) $o)>{{ $o }}</option>@endforeach</select>
                    @elseif ($a->type === 'boolean')
                        <div class="form-check form-switch mt-2"><input type="checkbox" role="switch" class="form-check-input" name="specs[{{ $a->key }}]" value="1" @checked($val)></div>
                    @else
                        <input type="{{ $a->type === 'number' ? 'number' : 'text' }}" @if ($a->type === 'number') step="any" @endif name="specs[{{ $a->key }}]" value="{{ $val }}" class="form-control @error("specs.{$a->key}") is-invalid @enderror">
                    @endif
                    @error("specs.{$a->key}")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div></div></div></div>
        @endif

        {{-- ADD-ONS --}}
        @if ($modifierGroups->isNotEmpty())
        <div class="tab-pane fade" id="pt-mods"><div class="card mb-3"><div class="card-body">
            <p class="text-muted small">{{ __('Choose the add-on groups customers see on this item.') }}</p>
            <div class="row g-2">
                @foreach ($modifierGroups as $g)
                    <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="modifier_groups[]" value="{{ $g->id }}" id="mg{{ $g->id }}" @checked(in_array($g->id, $pickedGroups, true))><label class="form-check-label" for="mg{{ $g->id }}"><b>{{ $g->name }}</b> <span class="text-muted small">— {{ $g->options()->pluck('name')->take(4)->implode(', ') }}</span></label></div></div>
                @endforeach
            </div>
        </div></div></div>
        @endif

        {{-- IMAGES --}}
        <div class="tab-pane fade" id="pt-images"><div class="card mb-3"><div class="card-body">
            @if ($editing && $product->getMedia('images')->isNotEmpty())
                <div class="d-flex flex-wrap gap-3 mb-3">
                    @foreach ($product->getMedia('images') as $m)
                        <div class="position-relative">
                            <img src="{{ $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl() }}" alt="" width="110" height="110" style="object-fit:cover;border-radius:12px;border:1px solid var(--border)">
                            <button type="submit" class="nx-icon-btn position-absolute" style="top:-8px;inset-inline-end:-8px;width:28px;height:28px" form="imgDel{{ $m->id }}" title="{{ __('Remove') }}"><i class="bi bi-x-lg"></i></button>
                        </div>
                    @endforeach
                </div>
            @endif
            <label class="form-label fw-medium">{{ __('Add images') }}</label>
            <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control @error('images.*') is-invalid @enderror">
            <div class="form-text">{{ __('JPG, PNG or WebP, up to 4 MB each. The first image is the main one.') }}</div>
            @error('images.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div></div></div>

        {{-- TRANSLATIONS --}}
        <div class="tab-pane fade" id="pt-tr"><div class="card mb-3"><div class="card-body">
            @include('partials.translation-fields', ['model' => $product, 'fields' => ['name' => 'Name', 'short_description' => 'Short description', 'description' => ['Description', 'textarea']]])
        </div></div></div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary px-4">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save product') }}</button>
    </div>
</form>

{{-- image delete forms live outside the main form (nested forms are invalid HTML) --}}
@if ($editing)
    @foreach ($product->getMedia('images') as $m)
        <form id="imgDel{{ $m->id }}" method="POST" action="{{ route('admin.products.images.destroy', [$product, $m->id]) }}" data-confirm="{{ __('Remove this image?') }}" class="d-none">@csrf @method('DELETE')</form>
    @endforeach
@endif

@push('scripts')
<script nonce="{{ csp_nonce() }}">
    (function () {
        var type = document.getElementById('pType'), rows = document.getElementById('varRows'), next = rows.children.length + 100;
        function sync() {
            var variable = type.value === 'variable';
            document.querySelectorAll('.simple-only').forEach(function (el) { el.style.display = variable ? 'none' : ''; });
            document.getElementById('tabVariantsLi').style.display = variable ? '' : 'none';
            if (variable && !rows.children.length) addRow();
        }
        function addRow() {
            var i = next++, tr = document.createElement('tr');
            tr.className = 'var-row';
            tr.innerHTML = '<td><input type="hidden" name="variants[' + i + '][id]" value=""><input name="variants[' + i + '][name]" class="form-control" style="min-width:140px" placeholder="Large / Red"></td>' +
                '<td><input name="variants[' + i + '][sku]" class="form-control figure" style="min-width:110px"></td>' +
                '<td><input type="number" step="0.01" min="0" name="variants[' + i + '][price]" class="form-control figure" style="min-width:100px"></td>' +
                '<td><input type="number" step="0.01" min="0" name="variants[' + i + '][compare_price]" class="form-control figure" style="min-width:100px"></td>' +
                '<td><input type="number" min="0" name="variants[' + i + '][stock_qty]" value="0" class="form-control figure" style="min-width:80px"></td>' +
                '<td><input type="hidden" name="variants[' + i + '][is_active]" value="0"><input type="checkbox" class="form-check-input" name="variants[' + i + '][is_active]" value="1" checked></td>' +
                '<td class="text-end"><button type="button" class="nx-icon-btn var-del"><i class="bi bi-trash3"></i></button></td>';
            rows.appendChild(tr);
        }
        document.getElementById('varAdd').addEventListener('click', addRow);
        rows.addEventListener('click', function (e) { var b = e.target.closest('.var-del'); if (b) b.closest('tr').remove(); });
        type.addEventListener('change', sync); sync();
    })();
</script>
@endpush
