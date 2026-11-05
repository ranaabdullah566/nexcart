@props(['label', 'value', 'sub' => null, 'tone' => 'default', 'icon' => 'activity'])

@php
$toneClass = ['default' => '', 'ok' => 'ok', 'warn' => 'warn', 'danger' => 'bad', 'bad' => 'bad', 'vio' => 'vio'][$tone] ?? '';
@endphp

<div class="nx-stat {{ $toneClass }}">
    <div class="d-flex align-items-center justify-content-between">
        <span class="nx-stat-label">{{ $label }}</span>
        <span class="nx-stat-ico"><i class="bi bi-{{ $icon }}"></i></span>
    </div>
    <div class="nx-stat-value">{{ $value }}</div>
    @if($sub)<div class="nx-stat-sub">{{ $sub }}</div>@endif
</div>
