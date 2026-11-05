@extends('layouts.admin')
@section('title', __('Staff'))

@section('content')
<x-page-header :title="__('Staff')" :eyebrow="__('Admin')" :sub="__('Team members who can sign in to the admin panel.')">
    <x-slot:actions>
        @can('staff.create')
            <a href="{{ route('admin.staff.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> {{ __('New staff member') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="card">
    <div class="card-body">
        <table id="staffTable" class="table align-middle w-100">
            <thead>
                <tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Role') }}</th><th>{{ __('Added') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @foreach ($staff as $member)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <span class="nx-chip-avatar">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span>
                                <span class="fw-semibold">{{ $member->name }}</span>
                            </div>
                        </td>
                        <td class="text-muted">{{ $member->email }}</td>
                        <td>@foreach ($member->roles as $r)<span class="badge rounded-pill text-bg-secondary">{{ __(ucfirst($r->name)) }}</span> @endforeach</td>
                        <td class="text-nowrap text-muted small" data-order="{{ $member->created_at?->timestamp }}">{{ $member->created_at?->format('d M Y') }}</td>
                        <td class="text-end">
                            <x-row-actions :edit="route('admin.staff.edit', $member)" :delete="route('admin.staff.destroy', $member)"
                                           :can="['edit' => 'staff.update', 'delete' => 'staff.delete']"
                                           :blocked="$member->is(auth()->user()) ? __('You cannot delete your own account.') : null"
                                           :confirm="__('Delete this staff member?')" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#staffTable').DataTable({
            responsive: true, pageLength: 10, lengthMenu: [5, 10, 25, 50], order: [[0, 'asc']],
            language: { search: "_INPUT_", searchPlaceholder: @json(__('Search staff…')) },
            columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { orderable: false, targets: -1 }]
        });
    });
</script>
@endpush
