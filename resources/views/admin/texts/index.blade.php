@extends('layouts.admin')
@section('title', __('Interface text'))

@section('content')
<x-page-header :title="__('Interface text')" :eyebrow="__('Storefront')" :sub="__('Change any word the shop shows — Cart to Basket, button names, messages. Type a new wording; clear it to go back to the built-in one.')">
    <x-slot:actions>
        @if ($overCount)<form method="POST" action="{{ route('admin.texts.reset') }}" data-no-ajax data-confirm="{{ __('Remove all your wording changes for this language?') }}" data-confirm-button="{{ __('Yes, reset') }}">@csrf @method('DELETE')<input type="hidden" name="locale" value="{{ $locale }}"><button class="btn btn-outline-danger"><i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('Reset this language') }} ({{ $overCount }})</button></form>@endif
    </x-slot:actions>
</x-page-header>

<form method="GET" class="card mb-3" data-no-validate><div class="card-body row g-2 align-items-end">
    <div class="col-12 col-md-3"><label class="form-label small">{{ __('Language') }}</label><select name="locale" class="form-select" data-autosubmit>@foreach ($locales as $code => $m)<option value="{{ $code }}" @selected($locale === $code)>{{ $m['label'] }}</option>@endforeach</select></div>
    <div class="col-12 col-md-7"><label class="form-label small">{{ __('Search') }}</label><input name="q" value="{{ $q }}" class="form-control" placeholder="{{ __('Find a word or sentence…') }}"></div>
    <div class="col-12 col-md-2 d-grid"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>{{ __('Search') }}</button></div>
</div></form>

<form method="POST" action="{{ route('admin.texts.update') }}" data-no-ajax data-no-validate>
    @csrf @method('PUT')
    <input type="hidden" name="locale" value="{{ $locale }}"><input type="hidden" name="q" value="{{ $q }}"><input type="hidden" name="page" value="{{ $rows->currentPage() }}">
    <div class="card"><div class="card-body table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th style="width:38%">{{ __('Original (English)') }}</th><th>{{ $locales[$locale]['label'] }}</th></tr></thead>
            <tbody>
            @forelse ($rows as $i => $key)
                @php $base = $builtin[$key] ?? $key; $mine = $over[$key] ?? null; @endphp
                <tr>
                    <td class="small">{{ $key }}</td>
                    <td><input type="hidden" name="keys[{{ $i }}]" value="{{ $key }}"><input name="vals[{{ $i }}]" dir="{{ $locales[$locale]['dir'] }}" value="{{ $mine ?? $base }}" maxlength="400" class="form-control form-control-sm {{ $mine !== null ? 'border-info' : '' }}">@if ($mine !== null)<div class="form-text mt-0"><i class="bi bi-pencil-fill"></i> {{ __('Your wording') }} · {{ __('Built-in') }}: {{ $base }}</div>@endif</td>
                </tr>
            @empty
                <tr><td colspan="2" class="text-center text-muted py-4">{{ __('Nothing found.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $rows->links() }}</div>
    </div></div>
    @if ($rows->count())<div class="d-flex justify-content-end mt-3"><button class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save this page') }}</button></div>@endif
</form>
@endsection
