@php $items = collect($s->settings['items'] ?? [])->filter(fn ($i) => filled($i['title'] ?? null)); @endphp
@if ($items->isNotEmpty())
<section class="sf-section sf-reveal">
    <div class="sf-section-head"><div><h2>{{ $s->text('title', __('How it works')) }}</h2>@if ($s->text('subtitle'))<p class="mk-sub">{{ $s->text('subtitle') }}</p>@endif</div></div>
    <ol class="mk-steps">
        @foreach ($items as $i => $it)
            @php $tr = $s->translations[app()->getLocale()]['items'][$i] ?? []; @endphp
            <li><span class="mk-step-n">@if (filled($it['icon'] ?? null))<i class="bi bi-{{ \App\Support\Sf::icon($it['icon']) }}"></i>@else{{ $loop->iteration }}@endif</span><div><b>{{ $tr['title'] ?? $it['title'] }}</b><p>{{ $tr['text'] ?? ($it['text'] ?? '') }}</p></div></li>
        @endforeach
    </ol>
</section>
@endif
