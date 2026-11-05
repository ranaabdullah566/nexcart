@extends('layouts.admin')
@section('title', __('Kitchen'))

@section('content')
<x-page-header :title="__('Kitchen')" :eyebrow="__('Live orders')" :sub="__('New orders appear here on their own. Tap a button to move an order along.')">
    <x-slot:actions>
        <button type="button" class="btn btn-outline-secondary" id="kdsSound" aria-pressed="false"><i class="bi bi-volume-mute-fill me-1"></i> <span>{{ __('Sound off') }}</span></button>
        <button type="button" class="btn btn-outline-secondary" id="kdsFull"><i class="bi bi-arrows-fullscreen me-1"></i> {{ __('Full screen') }}</button>
    </x-slot:actions>
</x-page-header>

<div id="kds" data-feed="{{ route('admin.kitchen.feed') }}">@include('admin.kitchen._board')</div>
@endsection

@push('styles')
<style>
    .kds-cols { display: grid; gap: 1rem; grid-template-columns: repeat(3, minmax(0, 1fr)); align-items: start; }
    @media (max-width: 991px) { .kds-cols { grid-template-columns: 1fr; } }
    .kds-col h5 { display: flex; justify-content: space-between; align-items: center; margin-bottom: .75rem; }
    .kds-card { border: 1px solid var(--border-strong); background: var(--surface); border-radius: 16px; padding: 1rem; margin-bottom: .75rem; border-inline-start: 5px solid var(--acc-1); }
    .kds-card.warn { border-inline-start-color: var(--warn); } .kds-card.late { border-inline-start-color: var(--bad); animation: kdsPulse 1.6s ease-in-out infinite; }
    .kds-card.blocked { opacity: .75; border-inline-start-color: var(--text-3); }
    .kds-num { font-family: var(--font-mono); font-weight: 700; font-size: 1.15rem; }
    .kds-timer { font-family: var(--font-mono); font-weight: 700; }
    .kds-item { display: flex; gap: .6rem; padding: .25rem 0; font-size: 1.05rem; } .kds-item b { min-width: 2ch; color: var(--acc-1); font-family: var(--font-mono); }
    .kds-mod { font-size: .85rem; color: var(--text-2); }
    @keyframes kdsPulse { 50% { box-shadow: 0 0 0 4px color-mix(in srgb, var(--bad) 30%, transparent); } }
    .kds-full .nx-sidebar, .kds-full .nx-topbar, .kds-full .nx-footer { display: none; }
    @media (prefers-reduced-motion: reduce) { .kds-card.late { animation: none; } }
</style>
@endpush

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    var root = document.getElementById('kds'), sound = false, last = +root.querySelector('[data-new]').dataset.new, ctx;
    function beep() {
        try { ctx = ctx || new (window.AudioContext || window.webkitAudioContext)(); var o = ctx.createOscillator(), g = ctx.createGain();
            o.type = 'sine'; o.frequency.value = 880; g.gain.value = .15; o.connect(g); g.connect(ctx.destination); o.start(); setTimeout(function () { o.frequency.value = 1175; }, 140); setTimeout(function () { o.stop(); }, 320); } catch (e) {}
    }
    function poll() {
        fetch(root.dataset.feed, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (r.status === 401 || r.status === 419) { location.reload(); throw 0; } return r.text(); })
            .then(function (html) {
                var t = document.createElement('div'); t.innerHTML = html;
                var n = +t.querySelector('[data-new]').dataset.new;
                if (n > last && sound) beep();
                last = n; root.innerHTML = html;
            }).catch(function () {});
    }
    setInterval(poll, 10000);
    document.getElementById('kdsSound').addEventListener('click', function () {
        sound = !sound; this.setAttribute('aria-pressed', sound);
        this.querySelector('i').className = 'bi me-1 ' + (sound ? 'bi-volume-up-fill' : 'bi-volume-mute-fill');
        this.querySelector('span').textContent = sound ? @json(__('Sound on')) : @json(__('Sound off'));
        if (sound) beep();
    });
    document.getElementById('kdsFull').addEventListener('click', function () {
        document.documentElement.classList.toggle('kds-full');
        if (document.documentElement.requestFullscreen && !document.fullscreenElement) document.documentElement.requestFullscreen().catch(function () {});
        else if (document.fullscreenElement) document.exitFullscreen();
    });
})();
</script>
@endpush
