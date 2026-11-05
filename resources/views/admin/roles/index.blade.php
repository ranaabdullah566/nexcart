@extends('layouts.admin')
@section('title', __('Roles & permissions'))
@section('content')
<x-page-header :title="__('Roles & permissions')" :eyebrow="__('System')" :sub="__('Decide exactly what each role can see and do.')">
    <x-slot:actions><a href="{{ route('admin.roles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New role') }}</a></x-slot:actions>
</x-page-header>
<div class="card"><div class="card-body table-responsive">
    <table class="table align-middle w-100 mb-0">
        <thead><tr><th>{{ __('Role') }}</th><th>{{ __('People') }}</th><th>{{ __('Permissions') }}</th><th class="text-end">{{ __('Actions') }}</th></tr></thead>
        <tbody>
        @foreach ($roles as $r)
            <tr>
                <td class="fw-semibold">{{ __(ucfirst($r->name)) }}</td>
                <td>{{ $r->users_count }}</td>
                <td><div class="progress" style="height:6px;min-width:120px"><div class="progress-bar" style="width:{{ $r->name === 'owner' ? 100 : round($r->permissions_count / max($total, 1) * 100) }}%"></div></div>
                    <span class="small text-muted">{{ $r->name === 'owner' ? $total : $r->permissions_count }} / {{ $total }}</span></td>
                <td class="text-end">
                    <a href="{{ route('admin.roles.edit', $r) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-sliders"></i> {{ $r->name === 'owner' ? __('View') : __('Edit') }}</a>
                    @unless (in_array($r->name, ['owner', 'manager', 'staff', 'rider', 'pharmacist'], true))
                        <form method="POST" action="{{ route('admin.roles.destroy', $r) }}" class="d-inline" data-confirm="{{ __('Delete the role “:r”?', ['r' => $r->name]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button></form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection
