@extends('store.account.layout', ['active' => 'prescriptions', 'heading' => __('Prescriptions')])
@section('panel')
<h1 class="ac-title">{{ __('My prescriptions') }}<a class="btn btn-primary btn-sm ms-auto" href="{{ route('store.prescription.upload') }}"><i class="bi bi-camera me-1"></i> {{ __('Upload new') }}</a></h1>
@forelse ($requests as $r)
    <article class="mk-box ac-rx">
        <header><div><b>{{ local_time($r->created_at, 'd M Y, h:i A') }}</b><small>{{ trans_choice(':n file|:n files', $r->media->count(), ['n' => $r->media->count()]) }}@if ($r->city) · {{ $r->city }}@endif</small></div>
            <span class="mk-status mk-status-{{ $r->status === 'closed' ? 'ok' : 'now' }}">{{ match ($r->status) { 'new' => __('Received'), 'contacted' => __('We called you'), default => __('Closed') } }}</span></header>
        @if ($r->notes)<p class="text-muted small mb-2">{{ $r->notes }}</p>@endif
        <div class="ac-files">@foreach ($r->media as $m)<a href="{{ route('store.account.prescriptions.file', [$r, $m->id]) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-image"></i> {{ __('File :n', ['n' => $loop->iteration]) }}</a>@endforeach</div>
    </article>
@empty
    <div class="mk-empty"><span><i class="bi bi-file-medical"></i></span><h2>{{ __('No prescriptions yet.') }}</h2><p>{{ __('Send a photo of your prescription and our pharmacist will call you back.') }}</p><a href="{{ route('store.prescription.upload') }}" class="btn btn-primary">{{ __('Upload prescription') }}</a></div>
@endforelse
@endsection
