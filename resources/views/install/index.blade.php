@extends('layouts.wizard')
@section('title', __('Install NexCart'))
@section('step', '1')
@section('headline', __('Let’s get NexCart running'))
@section('lead', __('We check your server, connect the database and create the tables. Then you set up your store.'))

@section('content')
<div class="mk-wz-head"><span class="mk-eyebrow">{{ __('Step :n of :t', ['n' => 1, 't' => 2]) }}</span><h1>{{ __('Install') }}</h1></div>

<section class="mk-wz-card">
    <h2 class="mk-box-title"><i class="bi bi-clipboard2-check"></i> {{ __('Server check') }}</h2>
    @php $good = collect($checks)->where('ok', true)->count(); @endphp
    <div class="mk-check-sum {{ $ready ? 'ok' : 'bad' }}"><i class="bi bi-{{ $ready ? 'check-circle-fill' : 'x-octagon-fill' }}"></i><b>{{ $ready ? __('Your server is ready') : __('Something needs your attention') }}</b><span>{{ __(':ok of :n checks passed', ['ok' => $good, 'n' => count($checks)]) }}</span></div>
    <details class="mk-check-details" @unless ($ready) open @endunless>
        <summary>{{ __('Show all checks') }}<i class="bi bi-chevron-down"></i></summary>
        <ul class="mk-checks">
            @foreach ($checks as $c)
                <li class="{{ $c['ok'] ? 'ok' : ($c['required'] ? 'bad' : 'warn') }}"><i class="bi bi-{{ $c['ok'] ? 'check-circle-fill' : ($c['required'] ? 'x-octagon-fill' : 'exclamation-circle-fill') }}"></i><span>{{ $c['label'] }}</span><em>{{ $c['detail'] }}</em></li>
            @endforeach
        </ul>
    </details>
    @unless ($ready)<div class="sf-alert mt-3" role="alert"><i class="bi bi-exclamation-triangle-fill fs-5"></i><div>{{ __('Some requirements are missing. Ask your hosting provider to enable them, then reload this page.') }}</div></div>@endunless
</section>

<form method="POST" action="{{ route('install.store') }}" data-no-ajax>
    @csrf
    <section class="mk-wz-card">
        <h2 class="mk-box-title"><i class="bi bi-database"></i> {{ __('Database') }}</h2>
        @foreach (['driver', 'host', 'database'] as $f)@error($f)<div class="sf-alert mb-3" role="alert"><i class="bi bi-exclamation-triangle-fill fs-5"></i><div>{{ $message }}</div></div>@enderror @endforeach

        <div class="mk-choice" role="radiogroup" aria-label="{{ __('Type') }}">
            <label class="mk-type"><input type="radio" name="driver" value="mysql" @checked(old('driver', 'mysql') === 'mysql')><span class="mk-type-body"><i class="bi bi-hdd-rack"></i><b>MySQL / MariaDB</b><small>{{ __('recommended') }}</small></span></label>
            <label class="mk-type"><input type="radio" name="driver" value="sqlite" @checked(old('driver') === 'sqlite')><span class="mk-type-body"><i class="bi bi-file-earmark-binary"></i><b>SQLite</b><small>{{ __('small stores and demos') }}</small></span></label>
        </div>

        <div class="row g-3 mt-1 my-only">
            <div class="col-md-8"><label class="form-label" for="dbHost">{{ __('Host') }}</label><input id="dbHost" name="host" value="{{ old('host', '127.0.0.1') }}" maxlength="190" class="form-control figure @error('host') is-invalid @enderror"></div>
            <div class="col-md-4"><label class="form-label" for="dbPort">{{ __('Port') }}</label><input id="dbPort" type="number" min="1" max="65535" name="port" value="{{ old('port', 3306) }}" class="form-control figure"></div>
            <div class="col-12"><label class="form-label" for="dbName">{{ __('Database name') }}</label><input id="dbName" name="database" value="{{ old('database', 'nexcart') }}" maxlength="64" class="form-control figure @error('database') is-invalid @enderror"><div class="form-text">{{ __('We will try to create it if it does not exist.') }}</div></div>
            <div class="col-md-6"><label class="form-label" for="dbUser">{{ __('Username') }}</label><input id="dbUser" name="username" value="{{ old('username', 'root') }}" autocomplete="off" maxlength="100" class="form-control figure"></div>
            <div class="col-md-6"><label class="form-label" for="dbPass">{{ __('Password') }}</label><div class="mk-field"><i class="bi bi-lock"></i><input id="dbPass" type="password" name="password" autocomplete="new-password" maxlength="200" class="form-control"><button type="button" class="mk-eye" data-pw-toggle aria-label="{{ __('Show password') }}"><i class="bi bi-eye"></i></button></div></div>
        </div>
        <div class="mk-note sq-only"><i class="bi bi-info-circle"></i> {{ __('The database will be a single file:') }} <code>{{ $sqlite }}</code></div>
    </section>
    <button class="btn btn-primary btn-lg w-100" @disabled(! $ready)><i class="bi bi-database-check me-1"></i> {{ __('Connect and create tables') }}</button>
</form>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
    (function () {
        var radios = document.querySelectorAll('input[name=driver]');
        function sync() {
            var v = (document.querySelector('input[name=driver]:checked') || {}).value || 'mysql';
            document.querySelectorAll('.my-only').forEach(function (e) { e.style.display = v === 'mysql' ? '' : 'none'; });
            document.querySelectorAll('.sq-only').forEach(function (e) { e.style.display = v === 'sqlite' ? '' : 'none'; });
        }
        radios.forEach(function (r) { r.addEventListener('change', sync); }); sync();
    })();
</script>
@endpush
