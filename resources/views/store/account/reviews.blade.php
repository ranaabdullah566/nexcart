@extends('store.account.layout', ['active' => 'reviews', 'heading' => __('My reviews')])
@section('panel')
<h1 class="ac-title">{{ __('My reviews') }}</h1>
@forelse ($reviews as $r)
    <article class="mk-box">
        <header class="ac-row"><div><b>@if ($r->product)<a href="{{ route('store.product', $r->product->slug) }}">{{ $r->product->name }}</a>@else {{ __('Removed product') }} @endif</b><small>{{ local_time($r->created_at, 'd M Y') }}</small></div>
            <span class="mk-status mk-status-{{ $r->status === 'approved' ? 'ok' : 'now' }}">{{ match ($r->status) { 'approved' => __('Published'), 'hidden' => __('Hidden'), default => __('Awaiting approval') } }}</span></header>
        <div class="ac-stars" aria-label="{{ $r->rating }}/5">@for ($i = 1; $i <= 5; $i++)<i class="bi bi-star{{ $i <= $r->rating ? '-fill' : '' }}"></i>@endfor</div>
        @if ($r->title)<b>{{ $r->title }}</b>@endif
        @if ($r->body)<p class="mb-1">{{ $r->body }}</p>@endif
        @if ($r->reply)<p class="small text-muted mb-0"><i class="bi bi-reply"></i> {{ $r->reply }}</p>@endif
    </article>
@empty
    <div class="mk-empty"><span><i class="bi bi-star"></i></span><h2>{{ __('No reviews yet.') }}</h2><p>{{ __('After your order arrives, open it to review the medicines you bought.') }}</p><a href="{{ route('store.account.orders') }}" class="btn btn-primary">{{ __('My orders') }}</a></div>
@endforelse
@endsection
