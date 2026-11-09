@extends('layouts.admin')
@section('title', __('Review'))
@section('content')
<x-page-header :back="route('admin.reviews.index')" :title="$review->product?->name ?? __('Review')" :eyebrow="__('Review')" />
<div class="card nx-form-card"><div class="card-body p-4">
    @unless ($review->added_by)
    <div class="mb-3"><span class="sf-stars" style="color:#fbbf24">@include('store._stars', ['value' => $review->rating])</span> <b>{{ $review->title }}</b>
        <p class="mb-1 mt-1">{{ $review->body }}</p><div class="small text-muted">{{ $review->name }} · {{ local_time($review->created_at) }}</div></div>
    @else
    <div class="small text-muted mb-3"><i class="bi bi-person-badge"></i> {{ __('Added by :name', ['name' => $review->author?->name ?? __('staff')]) }}</div>
    @endunless
    <form method="POST" action="{{ route('admin.reviews.update', $review) }}">
        @csrf @method('PUT')
        @if ($review->added_by)<div class="mb-3">@include('admin.reviews._fields', ['review' => $review])</div>@endif
        <div class="mb-3"><label class="form-label fw-medium">{{ __('Status') }}</label>
            <select name="status" class="form-select">@foreach (\App\Models\Review::STATUSES as $s)<option value="{{ $s }}" @selected(old('status', $review->status) === $s)>{{ __(ucfirst($s)) }}</option>@endforeach</select></div>
        <div class="mb-3"><label class="form-label fw-medium">{{ __('Public reply') }}</label><textarea name="reply" rows="3" maxlength="1000" class="form-control">{{ old('reply', $review->reply) }}</textarea><div class="form-text">{{ __('Shown under the review in the store.') }}</div></div>
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-secondary px-4 nx-cancel">{{ __('Cancel') }}</a><button class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> {{ __('Save') }}</button></div>
    </form>
</div></div>
@endsection
