@props(['title', 'eyebrow' => null, 'sub' => null, 'back' => null, 'compact' => false])

<div class="nx-head {{ $compact ? 'compact' : '' }}">
    <div class="d-flex align-items-center gap-3 min-w-0">
        @if($back)
            <a href="{{ $back }}" class="nx-back" title="Back"><i class="bi bi-arrow-left"></i></a>
        @endif
        <div class="min-w-0">
            @if($eyebrow)<div class="nx-eyebrow">{{ $eyebrow }}</div>@endif
            <h1 class="nx-title">{{ $title }}</h1>
            @if($sub)<p class="nx-sub">{{ $sub }}</p>@endif
        </div>
    </div>
    @if(isset($actions) && !$actions->isEmpty())
        <div class="d-flex align-items-center gap-2 flex-wrap">{{ $actions }}</div>
    @endif
</div>
