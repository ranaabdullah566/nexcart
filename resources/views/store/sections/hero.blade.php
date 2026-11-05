<section class="sf-hero sf-reveal" aria-label="{{ $s->text('title') }}">
    @if ($img = $s->imageUrl())<div class="sf-hero-img" style="background-image:url('{{ $img }}')"></div>@endif
    @if ($s->text('eyebrow'))<span class="sf-eyebrow">{{ $s->text('eyebrow') }}</span>@endif
    <h1>{{ $s->text('title', setting('store.name')) }}</h1>
    @if ($s->text('subtitle'))<p>{{ $s->text('subtitle') }}</p>@endif
    <div class="actions">
        @if ($s->text('button_label'))<a href="{{ $s->settings['button_url'] ?? route('store.shop') }}" class="btn btn-primary btn-lg px-4">{{ $s->text('button_label') }} <i class="bi bi-arrow-right ms-1"></i></a>@endif
        @if ($s->text('button2_label'))<a href="{{ $s->settings['button2_url'] ?? route('store.shop') }}" class="sf-btn-ghost">{{ $s->text('button2_label') }}</a>@endif
    </div>
</section>
