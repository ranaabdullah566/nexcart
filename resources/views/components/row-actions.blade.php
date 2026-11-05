@props(['show' => null, 'edit' => null, 'delete' => null, 'confirm' => null, 'blocked' => null, 'can' => []])

@php
    // $can = ['edit' => 'staff.update', 'delete' => 'staff.delete'] — hide buttons the user may not use.
    $u = auth()->user();
    $show = $show && (! isset($can['show']) || $u->can($can['show'])) ? $show : null;
    $edit = $edit && (! isset($can['edit']) || $u->can($can['edit'])) ? $edit : null;
    $delete = $delete && (! isset($can['delete']) || $u->can($can['delete'])) ? $delete : null;
@endphp

<div class="nx-act">
    @if ($show)
        <a href="{{ $show }}" class="btn view" title="{{ __('View') }}"><i class="bi bi-eye"></i></a>
    @endif
    @if ($edit)
        <a href="{{ $edit }}" class="btn edit" title="{{ __('Edit') }}"><i class="bi bi-pencil-square"></i></a>
    @endif
    @if ($delete)
        @if ($blocked)
            <button type="button" class="btn del" disabled title="{{ $blocked }}"><i class="bi bi-trash3"></i></button>
        @else
            <form action="{{ $delete }}" method="POST" data-confirm="{{ $confirm ?? __('Delete this record?') }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn del" title="{{ __('Delete') }}"><i class="bi bi-trash3"></i></button>
            </form>
        @endif
    @endif
</div>
