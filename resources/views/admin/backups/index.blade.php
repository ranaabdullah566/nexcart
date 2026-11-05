@extends('layouts.admin')
@section('title', __('Backups'))

@section('content')
<x-page-header :title="__('Backups')" :eyebrow="__('System')" :sub="__('A backup is one file with your database and every uploaded picture or prescription. Keep copies somewhere safe.')">
    <x-slot:actions>
        <form method="POST" action="{{ route('admin.backups.store') }}" data-no-ajax>@csrf <button class="btn btn-primary"><i class="bi bi-cloud-arrow-down me-1"></i> {{ __('Back up now') }}</button></form>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3"><div class="card-body">
    <form method="POST" action="{{ route('admin.backups.settings') }}" data-no-ajax class="row g-3 align-items-end">
        @csrf
        <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="auto" value="1" id="bkAuto" @checked(old('auto', $auto))><label class="form-check-label" for="bkAuto"><b>{{ __('Back up automatically every night (2:30 am)') }}</b></label></div><div class="form-text">{{ __('Needs the scheduler (see System health).') }}</div></div>
        <div class="col-md-3"><label class="form-label">{{ __('Keep the newest') }}</label><input type="number" min="1" max="60" name="keep" value="{{ old('keep', $keep) }}" class="form-control figure"></div>
        <div class="col-md-3"><button class="btn btn-outline-secondary w-100"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button></div>
    </form>
</div></div>

<div class="card"><div class="card-body">
    <table class="table align-middle mb-0">
        <thead><tr><th>{{ __('Backup') }}</th><th>{{ __('Created') }}</th><th>{{ __('Size') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @forelse ($backups as $b)
            <tr><td class="figure">{{ $b['name'] }}</td><td class="text-muted small">{{ \Illuminate\Support\Carbon::createFromTimestamp($b['at'])->timezone(setting('store.timezone'))->format('d M Y, H:i') }}</td><td class="figure">{{ number_format($b['size'] / 1024 / 1024, 2) }} MB</td>
                <td class="text-end"><div class="nx-act">
                    <a href="{{ route('admin.backups.download', $b['name']) }}" class="btn view" title="{{ __('Download') }}" data-no-modal><i class="bi bi-download"></i></a>
                    <form action="{{ route('admin.backups.destroy', $b['name']) }}" method="POST" data-confirm="{{ __('Delete this backup?') }}">@csrf @method('DELETE')<button class="btn del" title="{{ __('Delete') }}"><i class="bi bi-trash3"></i></button></form>
                </div></td></tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('No backups yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="small text-muted mt-3"><i class="bi bi-info-circle me-1"></i>{{ __('To restore on the server run') }} <code>php artisan nexcart:restore nexcart-YYYYMMDD-HHMMSS.zip</code></div>
</div></div>
@endsection
