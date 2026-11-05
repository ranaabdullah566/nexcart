@php($items = collect($s->settings['items'] ?? [])->filter(fn ($i) => filled($i['title'] ?? null)))
@if ($items->isNotEmpty())
<section class="mk-trust sf-reveal" aria-label="{{ __('Why shop with us') }}">
    @foreach ($items as $i => $it)
        @php($tr = $s->translations[app()->getLocale()]['items'][$i] ?? [])
        <div class="mk-trust-item"><span class="mk-trust-ic"><i class="bi bi-{{ preg_match('/^[a-z0-9-]+$/', $it['icon'] ?? '') ? $it['icon'] : 'stars' }}"></i></span><div><b>{{ $tr['title'] ?? $it['title'] }}</b><span>{{ $tr['text'] ?? ($it['text'] ?? '') }}</span></div></div>
    @endforeach
</section>
@endif
