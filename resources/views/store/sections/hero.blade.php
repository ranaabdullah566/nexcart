{{-- one slide; store/home.blade.php wraps every hero section into a carousel --}}
@php $img = $s->imageUrl(); @endphp
<div class="mk-slide {{ ($first ?? false) ? 'is-active' : '' }}" @if ($img) style="--mk-photo:url('{{ $img }}')" @endif @if (! ($first ?? false)) aria-hidden="true" @endif>
    <div class="sf-container mk-slide-row">
        <div class="mk-slide-text">
            @if ($s->text('eyebrow'))<span class="mk-tag"><i class="bi bi-patch-check-fill"></i> {{ $s->text('eyebrow') }}</span>@endif
            <h{{ ($first ?? false) ? 1 : 2 }} class="mk-hero-title">{{ $s->text('title', setting('store.name')) }}</h{{ ($first ?? false) ? 1 : 2 }}>
            @if ($s->text('subtitle'))<p>{{ $s->text('subtitle') }}</p>@endif
            <div class="mk-hero-cta">
                @if ($s->text('button_label'))<a href="{{ $s->settings['button_url'] ?? route('store.shop') }}" class="mk-btn-accent">{{ $s->text('button_label') }} <i class="bi bi-arrow-right"></i></a>@endif
                @if ($s->text('button2_label'))<a href="{{ $s->settings['button2_url'] ?? route('store.shop') }}" class="mk-btn-outline">{{ $s->text('button2_label') }}</a>@endif
            </div>
        </div>
        @if (($deals ?? collect())->isNotEmpty())
            <aside class="mk-slide-panel" aria-label="{{ __('Top deals') }}">
                <h4><i class="bi bi-fire"></i> {{ sft('home.hero_deals_title') }}</h4>
                @foreach ($deals as $d)
                    @php $off = (! $d->isVariable() && $d->compare_price > $d->price) ? (int) round((1 - $d->price / $d->compare_price) * 100) : null; @endphp
                    <a class="mk-mini" href="{{ route('store.product', $d->slug) }}" @if (! ($first ?? false)) tabindex="-1" @endif>
                        <span class="mk-mini-img">@if ($t = $d->thumbUrl())<img src="{{ $t }}" alt="" loading="lazy">@else<i class="bi bi-box-seam"></i>@endif</span>
                        <span class="mk-mini-info"><b>{{ $d->trans('name') }}</b><small>{{ $d->category?->trans('name') }}</small></span>
                        <span class="mk-mini-price">{{ money($d->displayPrice()) }}@if ($off)<s>{{ money($d->compare_price) }}</s>@endif</span>
                    </a>
                @endforeach
            </aside>
        @endif
    </div>
</div>
