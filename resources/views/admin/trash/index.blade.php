@extends('layouts.admin')
@section('title', __('Recycle Bin'))

@section('content')
<x-page-header :title="__('Recycle Bin')" :eyebrow="__('System')" :sub="__('Deleted items wait here. Restore them, or erase them for good.')">
    <x-slot:actions>
        @if ($counts[$type] > 0)
            <form method="POST" action="{{ route('admin.trash.empty', $type) }}" data-confirm="{{ __('Permanently delete all :n items in “:type”?', ['n' => $counts[$type], 'type' => __($types[$type]['label'])]) }}" data-confirm-title="{{ __('Empty this list?') }}" data-confirm-button="{{ __('Yes, delete forever') }}" data-confirm-note="{{ __('This cannot be undone.') }}" data-no-ajax>@csrf @method('DELETE')
                <button class="btn btn-outline-danger"><i class="bi bi-trash3-fill me-1"></i> {{ __('Empty') }}</button></form>
        @endif
    </x-slot:actions>
</x-page-header>

<ul class="nav nav-pills mb-3 gap-2 flex-wrap">
    @foreach ($types as $k => $t)
        <li class="nav-item"><a class="nav-link {{ $type === $k ? 'active' : '' }}" href="{{ route('admin.trash.index', ['type' => $k]) }}" data-no-modal><i class="bi bi-{{ $t['icon'] }} me-1"></i>{{ __($t['label']) }} @if ($counts[$k])<span class="badge rounded-pill text-bg-secondary ms-1">{{ $counts[$k] }}</span>@endif</a></li>
    @endforeach
</ul>

<div class="card mb-3"><div class="card-body">
    @if ($records->isEmpty())
        <div class="text-center text-muted py-5"><i class="bi bi-recycle fs-1 d-block mb-2"></i>{{ __('Nothing here. Deleted :type will appear in this list.', ['type' => mb_strtolower(__($types[$type]['label']))]) }}</div>
    @else
        <table class="table align-middle mb-0">
            <thead><tr><th>{{ __('Item') }}</th><th>{{ __('Deleted') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
            <tbody>
            @foreach ($records as $r)
                <tr>
                    <td class="fw-semibold">{{ ($types[$type]['name'])($r) }}</td>
                    <td class="small text-muted text-nowrap">{{ local_time($r->deleted_at, 'd M Y, H:i') }}</td>
                    <td class="text-end"><div class="nx-act">
                        <form method="POST" action="{{ route('admin.trash.restore', [$type, $r->id]) }}" data-no-ajax>@csrf
                            <button class="btn view" title="{{ __('Restore') }}"><i class="bi bi-arrow-counterclockwise"></i></button></form>
                        <form method="POST" action="{{ route('admin.trash.destroy', [$type, $r->id]) }}" data-no-ajax
                              data-confirm="{{ __('Permanently delete “:name”?', ['name' => ($types[$type]['name'])($r)]) }}" data-confirm-title="{{ __('Delete forever?') }}" data-confirm-button="{{ __('Yes, delete forever') }}" data-confirm-note="{{ __('This cannot be undone.') }}">@csrf @method('DELETE')
                            <button class="btn del" title="{{ __('Delete permanently') }}"><i class="bi bi-x-octagon"></i></button></form>
                    </div></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div></div>

@can('system.manage')
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('admin.trash.settings') }}" data-no-ajax class="row g-3 align-items-end">
        @csrf
        <div class="col-md-6"><label class="form-label">{{ __('Empty the bin automatically after (days)') }}</label><input type="number" min="0" max="3650" name="keep_days" value="{{ old('keep_days', $keepDays) }}" class="form-control figure"><div class="form-text">{{ __('0 keeps deleted items until you erase them yourself. Needs the scheduler.') }}</div></div>
        <div class="col-md-3"><button class="btn btn-outline-secondary w-100"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button></div>
    </form>
</div></div>
@endcan
@endsection
