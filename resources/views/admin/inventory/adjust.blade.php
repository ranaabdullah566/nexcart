@extends('layouts.admin')
@section('title', __('Adjust stock'))

@section('content')
<x-page-header :back="route('admin.inventory.index')" :title="__('Adjust stock')" :eyebrow="__('Inventory')" />
<div class="card nx-form-card"><div class="card-body p-4">
<form method="POST" action="{{ route('admin.inventory.store') }}">
    @csrf
    @php($sel = old('item', $selected))
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label fw-medium">{{ __('Item') }} <span class="text-danger">*</span></label>
            <select name="item" required class="form-select @error('item') is-invalid @enderror" data-select>
                <option value="">— {{ __('Choose') }} —</option>
                @foreach ($products as $p)
                    @if ($p->isVariable())
                        @foreach ($p->variants as $v)<option value="v:{{ $v->id }}" @selected($sel === 'v:'.$v->id)>{{ $p->name }} · {{ $v->name }} ({{ $v->stock_qty }})</option>@endforeach
                    @else
                        <option value="p:{{ $p->id }}" @selected($sel === 'p:'.$p->id)>{{ $p->name }} ({{ $p->stock_qty }})</option>
                    @endif
                @endforeach
            </select>
            @error('item')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">{{ __('Direction') }}</label>
            <select name="direction" id="adjDir" class="form-select">
                <option value="in" @selected(old('direction', 'in') === 'in')>{{ __('Add stock') }}</option>
                <option value="out" @selected(old('direction') === 'out')>{{ __('Remove stock') }}</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">{{ __('Quantity') }} <span class="text-danger">*</span></label>
            <input type="number" name="qty" min="1" value="{{ old('qty') }}" required class="form-control figure @error('qty') is-invalid @enderror @error('change') is-invalid @enderror">
            @error('qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @error('change')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label fw-medium">{{ __('Reason') }}</label>
            <select name="reason" class="form-select">@foreach ($reasons as $r)<option value="{{ $r }}" @selected(old('reason', 'received') === $r)>{{ __(ucfirst($r)) }}</option>@endforeach</select>
        </div>

        @if ($useBatches)
            <div class="col-12 batch-box">
                <div class="small text-muted mb-2"><i class="bi bi-info-circle me-1"></i>{{ __('Adding stock needs a batch. Removing stock uses the batch that expires first.') }}</div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">{{ __('Batch number') }}</label><input name="batch_no" value="{{ old('batch_no') }}" class="form-control figure @error('batch_no') is-invalid @enderror">@error('batch_no')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label">{{ __('Expiry date') }}</label><input type="date" name="expires_at" value="{{ old('expires_at') }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label">{{ __('Unit cost') }}</label><input type="number" step="0.01" min="0" name="cost" value="{{ old('cost') }}" class="form-control figure"></div>
                </div>
            </div>
        @endif

        <div class="col-12"><label class="form-label fw-medium">{{ __('Note') }}</label><input name="note" maxlength="190" value="{{ old('note') }}" class="form-control"></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button>
    </div>
</form>
</div></div>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
    (function () {
        var dir = document.getElementById('adjDir'), box = document.querySelector('.batch-box');
        if (!box) return;
        function sync() { box.style.display = dir.value === 'in' ? '' : 'none'; }
        dir.addEventListener('change', sync); sync();
    })();
</script>
@endpush
