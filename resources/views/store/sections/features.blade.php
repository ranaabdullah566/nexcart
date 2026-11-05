@php($items = collect($s->settings['items'] ?? [])->filter(fn ($i) => filled($i['title'] ?? null)))
@if ($items->isNotEmpty())
<section class="sf-section sf-reveal">
    <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-{{ min(4, max(2, $items->count())) }}">
        @foreach ($items as $i => $it)
            @php($tr = $s->translations[app()->getLocale()]['items'][$i] ?? [])
            <div class="col"><div class="sf-feature"><i class="bi bi-{{ preg_match('/^[a-z0-9-]+$/', $it['icon'] ?? '') ? $it['icon'] : 'stars' }}"></i><div><b>{{ $tr['title'] ?? $it['title'] }}</b><span>{{ $tr['text'] ?? ($it['text'] ?? '') }}</span></div></div></div>
        @endforeach
    </div>
</section>
@endif
