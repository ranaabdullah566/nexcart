@extends('layouts.admin')
@section('title', __('Add review'))
@section('content')
<x-page-header :back="route('admin.reviews.index')" :title="__('Add review')" :eyebrow="__('Reviews')" :sub="__('Write a review yourself — for example one a customer sent by phone or WhatsApp. It is not marked as a verified buyer.')" />
<div class="card nx-form-card"><div class="card-body p-4">
    <form method="POST" action="{{ route('admin.reviews.store') }}">
        @csrf
        <div class="row g-3 mb-3">
            <div class="col-md-8"><label class="form-label fw-medium" for="rv_product">{{ __('Product') }} <span class="text-danger">*</span></label>
                <select id="rv_product" name="product_id" required data-select class="form-select @error('product_id') is-invalid @enderror"><option value="">— {{ __('Choose a product') }} —</option>@foreach ($products as $p)<option value="{{ $p->id }}" @selected((string) old('product_id', $preProduct) === (string) $p->id)>{{ $p->name }}</option>@endforeach</select>@error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label fw-medium" for="rv_status">{{ __('Status') }}</label>
                <select id="rv_status" name="status" class="form-select">@foreach (\App\Models\Review::STATUSES as $s)<option value="{{ $s }}" @selected(old('status', 'approved') === $s)>{{ __(ucfirst($s)) }}</option>@endforeach</select></div>
        </div>
        @include('admin.reviews._fields')
        <div class="mt-3"><label class="form-label fw-medium" for="rv_reply">{{ __('Public reply') }}</label><textarea id="rv_reply" name="reply" rows="2" maxlength="1000" class="form-control">{{ old('reply') }}</textarea></div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a><button class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button></div>
    </form>
</div></div>
@endsection
