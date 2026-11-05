@extends('layouts.admin')
@section('title', __('Activity log'))

@section('content')
<x-page-header :title="__('Activity log')" :eyebrow="__('System')" :sub="__('Who did what, and when — every change, sign-in and deletion.')">
    <x-slot:actions>
        @can('reports.export')<a class="btn btn-outline-secondary" href="{{ route('admin.activity.export', $f) }}" data-no-modal><i class="bi bi-download me-1"></i> CSV</a>@endcan
    </x-slot:actions>
</x-page-header>

<form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-12 col-md-3"><label class="form-label small">{{ __('Search') }}</label><input name="q" value="{{ $f['q'] ?? '' }}" class="form-control" placeholder="{{ __('Name, SKU, email…') }}"></div>
    <div class="col-6 col-md-2"><label class="form-label small">{{ __('User') }}</label><select name="user" class="form-select"><option value="">{{ __('Anyone') }}</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(($f['user'] ?? '') == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label small">{{ __('Event') }}</label><select name="event" class="form-select"><option value="">{{ __('Any') }}</option>@foreach ($events as $e)<option @selected(($f['event'] ?? '') === $e)>{{ $e }}</option>@endforeach</select></div>
    <div class="col-6 col-md-2"><label class="form-label small">{{ __('Record type') }}</label><select name="subject" class="form-select"><option value="">{{ __('Any') }}</option>@foreach ($subjects as $s)<option value="{{ $s['class'] }}" @selected(($f['subject'] ?? '') === $s['class'])>{{ $s['label'] }}</option>@endforeach</select></div>
    <div class="col-3 col-md-1"><label class="form-label small">{{ __('From') }}</label><input type="date" name="from" value="{{ $f['from'] ?? '' }}" class="form-control px-1"></div>
    <div class="col-3 col-md-1"><label class="form-label small">{{ __('To') }}</label><input type="date" name="to" value="{{ $f['to'] ?? '' }}" class="form-control px-1"></div>
    <div class="col-12 col-md-1 d-grid"><button class="btn btn-primary"><i class="bi bi-funnel"></i></button></div>
</div></form>

<div class="card"><div class="card-body table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>{{ __('When') }}</th><th>{{ __('Who') }}</th><th>{{ __('Event') }}</th><th>{{ __('Record') }}</th><th>{{ __('Details') }}</th><th>IP</th></tr></thead>
        <tbody>
        @forelse ($entries as $e)
            @php($p = $e->properties)
            <tr>
                <td class="small text-muted text-nowrap">{{ local_time($e->created_at, 'd M Y, H:i') }}</td>
                <td>{{ $e->causer?->name ?? '—' }}</td>
                <td><span class="badge text-bg-{{ in_array($e->event, ['deleted', 'trash.erased', 'trash.emptied', 'auth.failed']) ? 'danger' : (in_array($e->event, ['created', 'restored', 'trash.restored', 'auth.login']) ? 'success' : 'secondary') }}">{{ $e->event ?? $e->description }}</span></td>
                <td class="small">@if ($e->subject_type){{ class_basename($e->subject_type) }} #{{ $e->subject_id }} <span class="text-muted">{{ $e->subject?->name ?? $e->subject?->title ?? $e->subject?->code ?? $e->subject?->number ?? '' }}</span>@endif</td>
                <td class="small text-muted" style="max-width:420px">
                    @if ($p->has('attributes'))
                        @foreach ($p->get('attributes', []) as $k => $v)
                            <div class="text-truncate"><strong>{{ $k }}</strong>: @if ($p->has('old') && array_key_exists($k, $p->get('old')))<s class="text-danger">{{ \Illuminate\Support\Str::limit(is_scalar($p['old'][$k] ?? null) ? $p['old'][$k] : json_encode($p['old'][$k], JSON_UNESCAPED_UNICODE), 40) }}</s> → @endif{{ \Illuminate\Support\Str::limit(is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE), 60) }}</div>
                        @endforeach
                    @else
                        {{ $p->except(['ip', 'ua'])->map(fn ($v, $k) => $k.': '.(is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE)))->implode(' · ') }}
                    @endif
                </td>
                <td class="small font-monospace">{{ $p['ip'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Nothing recorded yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-3">{{ $entries->links() }}</div>
</div></div>
@endsection
