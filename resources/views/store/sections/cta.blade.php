<section class="sf-section sf-reveal">
    <div class="mk-cta" @if ($img = $s->imageUrl()) style="--mk-photo:url('{{ $img }}')" @endif>
        <div>
            @if ($s->text('eyebrow'))<span class="mk-tag">{{ $s->text('eyebrow') }}</span>@endif
            <h2>{{ $s->text('title') }}</h2>
            @if ($s->text('subtitle'))<p>{{ $s->text('subtitle') }}</p>@endif
        </div>
        <div class="mk-hero-cta">
            @if ($s->text('button_label'))<a href="{{ $s->settings['button_url'] ?? route('store.shop') }}" class="mk-btn-accent">{{ $s->text('button_label') }} <i class="bi bi-arrow-right"></i></a>@endif
            @if ($s->text('button2_label'))<a href="{{ $s->settings['button2_url'] ?? route('store.shop') }}" class="mk-btn-outline">{{ $s->text('button2_label') }}</a>@endif
        </div>
    </div>
</section>
