@php
    $cover = $a->coverUrl('card');
    $icon = \App\Support\Storefront::categoryIcon($a->tag ?: $a->title);
    $hue = crc32((string) $a->slug) % 360;
    $href = route('store.article', $a->slug);
@endphp
<article class="fx-art {{ ($featured ?? false) ? 'fx-art-lead' : '' }}">
    <a href="{{ $href }}" class="fx-art-cover" tabindex="-1" aria-hidden="true" style="--h: {{ $hue }}">
        @if ($cover)<img src="{{ $cover }}" alt="" loading="lazy">@else<span class="fx-art-ph"><i class="bi bi-{{ $icon }}"></i></span>@endif
        @if ($a->tag)<span class="fx-art-tag">{{ $a->trans('tag') }}</span>@endif
    </a>
    <div class="fx-art-body">
        <div class="fx-art-meta"><time datetime="{{ $a->date()->toDateString() }}">{{ $a->date()->translatedFormat('d M Y') }}</time><span>·</span><span><i class="bi bi-clock"></i> {{ __(':n min read', ['n' => $a->readingMinutes()]) }}</span></div>
        <h{{ $level ?? 3 }}><a href="{{ $href }}" dir="auto">{{ $a->trans('title') }}</a></h{{ $level ?? 3 }}>
        <p dir="auto">{{ $a->summary(($featured ?? false) ? 220 : 130) }}</p>
        <a href="{{ $href }}" class="fx-art-more">{{ __('Read article') }} <i class="bi bi-arrow-right"></i></a>
    </div>
</article>
