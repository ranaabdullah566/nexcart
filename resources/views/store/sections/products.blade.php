@if ($products->isNotEmpty())
<section class="sf-section sf-reveal ps" data-pslider data-autoplay="4800" aria-roledescription="carousel" aria-label="{{ $s->text('title', __('New arrivals')) }}">
    <div class="sf-section-head ps-head">
        <div>
            <h2>{{ $s->text('title', __('New arrivals')) }}</h2>
            <p class="mk-sub">{{ ['featured' => __('Hand-picked for you.'), 'new' => __('Fresh in the store.'), 'category' => __('A closer look.')][$s->settings['source'] ?? 'new'] ?? '' }}</p>
        </div>
        <div class="ps-tools">
            <a href="{{ route('store.shop') }}" class="ps-all">{{ __('See all') }} <i class="bi bi-arrow-right"></i></a>
            <div class="ps-nav">
                <button type="button" class="ps-btn" data-ps-prev aria-label="{{ __('Previous') }}"><i class="bi bi-arrow-left"></i></button>
                <button type="button" class="ps-btn" data-ps-next aria-label="{{ __('Next') }}"><i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
    </div>
    <div class="ps-viewport" data-ps-viewport>
        <div class="ps-track" data-ps-track tabindex="0" role="group" aria-label="{{ __('Products') }}">
            @foreach ($products as $p)<div class="ps-item" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}">@include('store._card', ['p' => $p])</div>@endforeach
        </div>
    </div>
    <div class="ps-foot">
        <div class="ps-bar" data-ps-bar role="presentation"><i data-ps-thumb></i></div>
        <span class="ps-count" data-ps-count aria-live="off"></span>
        <button type="button" class="ps-play" data-ps-play aria-pressed="true" aria-label="{{ __('Pause') }}"><i class="bi bi-pause-fill"></i></button>
    </div>
</section>
@endif
