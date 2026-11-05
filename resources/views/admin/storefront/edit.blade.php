@extends('layouts.admin')
@section('title', __('Storefront'))

@section('content')
<x-page-header :title="__('Storefront')" :eyebrow="__('Storefront')" :sub="__('Switch parts of your shop on or off and change their wording. Language boxes you leave empty fall back to English, then to the built-in text.')">
    <x-slot:actions><a href="{{ route('store.home') }}" target="_blank" class="btn btn-outline-secondary" data-no-modal><i class="bi bi-box-arrow-up-right me-1"></i> {{ __('View store') }}</a></x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('admin.storefront.update') }}" data-no-ajax id="sfForm">
    @csrf @method('PUT')
    <input type="hidden" name="tab" id="sfTab" value="{{ request('tab', array_key_first($groups)) }}">
    @php $active = request('tab', array_key_first($groups)); @endphp
    <ul class="nav nav-pills mb-3 gap-2 flex-wrap" role="tablist">
        @foreach ($groups as $g => [$label, $icon])
            <li class="nav-item"><button type="button" class="nav-link {{ $active === $g ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#sfg-{{ $g }}" data-tab-key="{{ $g }}"><i class="bi bi-{{ $icon }} me-1"></i>{{ __($label) }}</button></li>
        @endforeach
    </ul>
    <div class="tab-content">
        @foreach ($groups as $g => [$label, $icon])
            <div class="tab-pane fade {{ $active === $g ? 'show active' : '' }}" id="sfg-{{ $g }}">
                <div class="card mb-3"><div class="card-body">
                    <h5 class="mb-3">{{ __($label) }}</h5>
                    <div class="row g-3">
                        @foreach ($schema as $key => $def) @continue($def['group'] !== $g)
                            @php $f = 'v['.str_replace('.', '_', $key).']'; @endphp
                            @switch($def['type'])
                                @case('bool')
                                    <div class="col-md-6"><div class="form-check form-switch"><input type="checkbox" role="switch" class="form-check-input" id="o-{{ $key }}" name="{{ $f }}" value="1" @checked(\App\Support\Sf::get($key))><label class="form-check-label" for="o-{{ $key }}">{{ __($def['label']) }}</label></div>@if ($def['hint'] ?? null)<div class="form-text">{{ __($def['hint']) }}</div>@endif</div>
                                    @break
                                @case('int')
                                    <div class="col-md-4"><label class="form-label" for="o-{{ $key }}">{{ __($def['label']) }}</label><input type="number" id="o-{{ $key }}" name="{{ $f }}" min="{{ $def['min'] }}" max="{{ $def['max'] }}" value="{{ \App\Support\Sf::get($key) }}" class="form-control figure"></div>
                                    @break
                                @case('select')
                                    <div class="col-md-4"><label class="form-label" for="o-{{ $key }}">{{ __($def['label']) }}</label><select id="o-{{ $key }}" name="{{ $f }}" class="form-select">@foreach ($def['options'] as $v => $l)<option value="{{ $v }}" @selected((string) \App\Support\Sf::get($key) === (string) $v)>{{ __($l) }}</option>@endforeach</select></div>
                                    @break
                                @case('string')
                                    <div class="col-md-6"><label class="form-label" for="o-{{ $key }}">{{ __($def['label']) }}</label><input id="o-{{ $key }}" name="{{ $f }}" maxlength="{{ $def['max'] ?? 190 }}" value="{{ \App\Support\Sf::get($key) }}" class="form-control"></div>
                                    @break
                                @default
                                    <div class="col-12"><label class="form-label fw-medium">{{ __($def['label']) }}</label>@if ($def['hint'] ?? null)<div class="form-text mt-0 mb-1">{{ __($def['hint']) }}</div>@endif
                                        <div class="row g-2">
                                            @foreach ($locales as $code => $m)
                                                <div class="col-md-{{ $def['type'] === 'lines' ? 12 : 4 }}"><div class="input-group input-group-sm"><span class="input-group-text">{{ strtoupper(explode('_', $code)[0]) }}{{ str_contains($code, '_') ? '·'.str_replace('_', '', explode('_', $code)[1]) : '' }}</span>
                                                    @if ($def['type'] === 'lines')<textarea name="{{ $f }}[{{ $code }}]" rows="3" dir="{{ $m['dir'] }}" maxlength="1500" class="form-control" placeholder="{{ $code === 'en' ? str_replace("\n", ' ⏎ ', $def['default']) : '' }}">{{ \App\Support\Sf::stored($key, $code) }}</textarea>
                                                    @else<input name="{{ $f }}[{{ $code }}]" dir="{{ $m['dir'] }}" maxlength="400" value="{{ \App\Support\Sf::stored($key, $code) }}" class="form-control" placeholder="{{ $code === 'en' ? $def['default'] : '' }}">@endif
                                                </div></div>
                                            @endforeach
                                        </div></div>
                            @endswitch
                        @endforeach
                    </div>
                </div></div>
            </div>
        @endforeach
    </div>
    <div class="d-flex justify-content-end"><button class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save storefront') }}</button></div>
</form>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">document.querySelectorAll('[data-tab-key]').forEach(function (b) { b.addEventListener('shown.bs.tab', function () { document.getElementById('sfTab').value = b.dataset.tabKey; }); });</script>
@endpush
