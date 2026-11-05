@extends('layouts.admin')
@section('title', __('Activity log'))

@section('content')
<x-page-header :title="__('Activity log')" :eyebrow="__('System')" :sub="__('Sign-ins, security changes and deletions, newest first.')" />
<div class="card"><div class="card-body table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>{{ __('When') }}</th><th>{{ __('Who') }}</th><th>{{ __('Event') }}</th><th>{{ __('Details') }}</th><th>IP</th></tr></thead>
        <tbody>
        @forelse ($entries as $e)
            <tr>
                <td class="small text-muted text-nowrap">{{ local_time($e->created_at, 'd M Y, H:i') }}</td>
                <td>{{ $e->causer?->name ?? '—' }}</td>
                <td><span class="badge text-bg-secondary">{{ $e->event ?? $e->description }}</span></td>
                <td class="small text-muted">{{ collect($e->properties)->except(['ip', 'ua'])->map(fn ($v, $k) => $k.': '.(is_scalar($v) ? $v : json_encode($v)))->implode(' · ') }}</td>
                <td class="small font-monospace">{{ $e->properties['ip'] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('Nothing recorded yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
