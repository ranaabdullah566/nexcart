<section class="sf-section sf-reveal">
    <div class="sf-banner">
        @if ($img = $s->imageUrl())<div class="sf-banner-img" style="background-image:url('{{ $img }}')"></div>@endif
        <div>
            @if ($s->text('eyebrow'))<span class="sf-eyebrow">{{ $s->text('eyebrow') }}</span>@endif
            <h2 class="mt-2">{{ $s->text('title') }}</h2>
            @if ($s->text('subtitle'))<p class="text-muted mb-3" style="max-width:48ch">{{ $s->text('subtitle') }}</p>@endif
            @if ($s->text('button_label'))<a href="{{ $s->settings['button_url'] ?? route('store.shop') }}" class="btn btn-primary px-4">{{ $s->text('button_label') }}</a>@endif
        </div>
    </div>
</section>
