@php $items = collect($s->settings['items'] ?? [])->filter(fn ($i) => filled($i['title'] ?? null) && filled($i['text'] ?? null)); @endphp
@if ($items->isNotEmpty())
<section class="sf-section sf-reveal">
    <div class="sf-section-head"><div><h2>{{ $s->text('title', __('What customers say')) }}</h2>@if ($s->text('subtitle'))<p class="mk-sub">{{ $s->text('subtitle') }}</p>@endif</div></div>
    <div class="mk-quotes">
        @foreach ($items as $i => $it)
            @php $tr = $s->translations[app()->getLocale()]['items'][$i] ?? []; @endphp
            <figure class="mk-quote"><i class="bi bi-quote"></i><blockquote dir="auto">{{ $tr['text'] ?? $it['text'] }}</blockquote><figcaption><span class="mk-avatar">{{ mb_strtoupper(mb_substr($tr['title'] ?? $it['title'], 0, 1)) }}</span><div><b>{{ $tr['title'] ?? $it['title'] }}</b>@if (filled($tr['role'] ?? $it['role'] ?? null))<small>{{ $tr['role'] ?? $it['role'] }}</small>@endif</div></figcaption></figure>
        @endforeach
    </div>
</section>
@endif
