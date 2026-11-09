@extends('layouts.admin')
@section('title', __('Prescription :number', ['number' => $order->number]))

@section('content')
<x-page-header :back="route('admin.prescriptions.index')" :title="$order->number" :eyebrow="__('Prescription review')" :sub="$order->name.' · '.$order->phone" />
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3"><div class="card-body">
            <h2 class="text-uppercase text-muted small h6">{{ __('Uploaded files') }}</h2>
            <div class="d-grid gap-3">
                @forelse ($order->getMedia('prescriptions') as $m)
                    @php($url = route('admin.orders.prescription', [$order, $m->id]))
                    @if (str_starts_with($m->mime_type, 'image/'))
                        <a href="{{ $url }}" target="_blank" data-no-modal><img src="{{ $url }}" alt="{{ $m->file_name }}" class="img-fluid rounded-3" style="max-height:420px;border:1px solid var(--border)"></a>
                    @else
                        <a href="{{ $url }}" target="_blank" class="btn btn-outline-secondary text-start" data-no-modal><i class="bi bi-file-earmark-pdf me-2"></i>{{ $m->file_name }}</a>
                    @endif
                @empty<span class="text-muted">{{ __('No file') }}</span>@endforelse
            </div>
        </div></div>
        <div class="card"><div class="card-body">
            <h2 class="text-uppercase text-muted small h6">{{ __('Medicines ordered') }}</h2>
            @foreach ($order->items as $i)
                <div class="d-flex justify-content-between py-1"><span>{{ $i->qty }} × <b>{{ $i->name }}</b>@if ($i->variant_name) · {{ $i->variant_name }}@endif @if ($i->product?->requires_prescription)<span class="badge text-bg-warning ms-1">Rx</span>@endif</span></div>
            @endforeach
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <h2 class="text-uppercase text-muted small h6">{{ __('Decision') }}</h2>
            <p>{{ __('Status') }}: <span class="badge rounded-pill text-bg-{{ ['approved' => 'success', 'rejected' => 'danger', 'pending' => 'warning'][$order->prescription_status] ?? 'secondary' }}">{{ __(ucfirst((string) $order->prescription_status)) }}</span>
                @if ($order->prescriptionReviewer)<span class="small text-muted"> · {{ $order->prescriptionReviewer->name }}, {{ local_time($order->prescription_reviewed_at, 'd M H:i') }}</span>@endif</p>
            @if ($order->prescription_note)<p class="small">{{ $order->prescription_note }}</p>@endif
            @if ($order->isOpen() && auth()->user()->can('prescriptions.verify'))
                <form method="POST" action="{{ route('admin.prescriptions.review', $order) }}" data-no-ajax>
                    @csrf
                    <textarea name="note" rows="2" maxlength="190" class="form-control mb-3" placeholder="{{ __('Note for the customer (required when sending back)') }}"></textarea>
                    <div class="d-flex gap-2">
                        <button name="decision" value="approved" class="btn btn-primary flex-grow-1"><i class="bi bi-patch-check me-1"></i> {{ __('Approve') }}</button>
                        <button name="decision" value="rejected" class="btn btn-outline-danger flex-grow-1"><i class="bi bi-arrow-return-left me-1"></i> {{ __('Send back') }}</button>
                    </div>
                </form>
            @endif
        </div></div>
    </div>
</div>
@endsection
