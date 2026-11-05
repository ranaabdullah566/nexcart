@extends('layouts.guest')
@section('title', __('Install NexCart'))

@section('content')
<div class="mb-4">
    <div class="nx-eyebrow">NexCart · {{ __('Step 1 of 2') }}</div>
    <h1 class="nx-title">{{ __('Install') }}</h1>
    <p class="nx-sub">{{ __('We check your server, connect the database and create the tables. Then you set up your store.') }}</p>
</div>

<div class="card mb-3"><div class="card-body">
    <h5 class="mb-3">{{ __('1 · Server check') }}</h5>
    <div class="row g-2">
        @foreach ($checks as $c)
            <div class="col-md-6"><div class="d-flex align-items-center gap-2 small"><i class="bi bi-{{ $c['ok'] ? 'check-circle-fill text-success' : ($c['required'] ? 'x-octagon-fill text-danger' : 'exclamation-circle-fill text-warning') }}"></i><span>{{ $c['label'] }}</span><span class="ms-auto text-muted">{{ $c['detail'] }}</span></div></div>
        @endforeach
    </div>
    @unless ($ready)<div class="alert alert-danger mt-3 mb-0 py-2">{{ __('Some requirements are missing. Ask your hosting provider to enable them, then reload this page.') }}</div>@endunless
</div></div>

<form method="POST" action="{{ route('install.store') }}" data-no-ajax>
    @csrf
    <div class="card mb-3"><div class="card-body">
        <h5 class="mb-3">{{ __('2 · Database') }}</h5>
        @error('driver')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
        @error('host')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
        <div class="mb-3"><label class="form-label">{{ __('Type') }}</label>
            <select name="driver" id="drv" class="form-select"><option value="mysql" @selected(old('driver', 'mysql') === 'mysql')>MySQL / MariaDB ({{ __('recommended') }})</option><option value="sqlite" @selected(old('driver') === 'sqlite')>SQLite ({{ __('small stores and demos') }})</option></select></div>
        <div class="row g-3 my-only">
            <div class="col-md-8"><label class="form-label">{{ __('Host') }}</label><input name="host" value="{{ old('host', '127.0.0.1') }}" class="form-control figure @error('host') is-invalid @enderror"></div>
            <div class="col-md-4"><label class="form-label">{{ __('Port') }}</label><input name="port" value="{{ old('port', 3306) }}" class="form-control figure"></div>
            <div class="col-12"><label class="form-label">{{ __('Database name') }}</label><input name="database" value="{{ old('database', 'nexcart') }}" class="form-control figure @error('database') is-invalid @enderror"><div class="form-text">{{ __('We will try to create it if it does not exist.') }}</div></div>
            <div class="col-md-6"><label class="form-label">{{ __('Username') }}</label><input name="username" value="{{ old('username', 'root') }}" autocomplete="off" class="form-control figure"></div>
            <div class="col-md-6"><label class="form-label">{{ __('Password') }}</label><input type="password" name="password" autocomplete="new-password" class="form-control"></div>
        </div>
        <div class="small text-muted sq-only">{{ __('The database will be a single file:') }} <code>{{ $sqlite }}</code></div>
    </div></div>
    <button class="btn btn-primary btn-lg w-100" @disabled(! $ready)><i class="bi bi-database-check me-1"></i> {{ __('Connect and create tables') }}</button>
</form>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">(function () { var d = document.getElementById('drv'); function f() { document.querySelectorAll('.my-only').forEach(function (e) { e.style.display = d.value === 'mysql' ? '' : 'none'; }); document.querySelectorAll('.sq-only').forEach(function (e) { e.style.display = d.value === 'sqlite' ? '' : 'none'; }); } d.addEventListener('change', f); f(); })();</script>
@endpush
