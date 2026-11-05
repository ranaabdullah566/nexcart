@php $items = collect($s->settings['items'] ?? [])->filter(fn ($i) => filled($i['title'] ?? null) && filled($i['text'] ?? null)); @endphp
@if ($items->isNotEmpty())
<section class="sf-section sf-reveal">
    <div class="sf-section-head"><div><h2>{{ $s->text('title', __('Questions & answers')) }}</h2>@if ($s->text('subtitle'))<p class="mk-sub">{{ $s->text('subtitle') }}</p>@endif</div></div>
    <div class="mk-faq">
        @foreach ($items as $i => $it)
            @php $tr = $s->translations[app()->getLocale()]['items'][$i] ?? []; @endphp
            <details @if ($loop->first) open @endif><summary>{{ $tr['title'] ?? $it['title'] }}<i class="bi bi-chevron-down"></i></summary><p dir="auto">{{ $tr['text'] ?? $it['text'] }}</p></details>
        @endforeach
    </div>
</section>
@endif
