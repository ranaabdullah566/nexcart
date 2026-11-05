{{-- Urdu / Roman Urdu copies of text fields.  @include('partials.translation-fields', ['model' => $x ?? null, 'fields' => ['name' => 'Name', 'description' => ['Description', 'textarea']]]) --}}
@php($locales = collect(\App\Support\Locales::all())->except('en'))
<div class="nx-tr mt-4 pt-3" style="border-top:1px dashed var(--border)">
    <div class="small text-muted mb-2"><i class="bi bi-translate me-1"></i>{{ __('Translations (optional) — shown when a visitor picks that language.') }}</div>
    <div class="row g-3">
        @foreach ($locales as $code => $meta)
            @foreach ($fields as $field => $def)
                @php([$label, $kind] = is_array($def) ? $def : [$def, 'input'])
                <div class="col-12 col-md-6">
                    <label class="form-label small">{{ __($label) }} · {{ $meta['label'] }}</label>
                    @if ($kind === 'textarea')
                        <textarea name="tr[{{ $code }}][{{ $field }}]" rows="3" dir="{{ $meta['dir'] }}" class="form-control">{{ old("tr.$code.$field", $model?->translations[$code][$field] ?? '') }}</textarea>
                    @else
                        <input name="tr[{{ $code }}][{{ $field }}]" dir="{{ $meta['dir'] }}" value="{{ old("tr.$code.$field", $model?->translations[$code][$field] ?? '') }}" class="form-control">
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>
</div>
