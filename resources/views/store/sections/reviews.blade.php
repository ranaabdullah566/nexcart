@php
    $rv = $reviewData ?? ['list' => collect(), 'count' => 0];
    $list = $rv['list'];
@endphp
@if ($list->isNotEmpty())
<section class="sf-section sf-reveal fx-rev-sec" aria-labelledby="rev-{{ $s->id }}">
    <div class="sf-section-head"><div><h2 id="rev-{{ $s->id }}">{{ $s->text('title', __('What our customers say')) }}</h2>@if ($s->text('subtitle'))<p class="mk-sub">{{ $s->text('subtitle') }}</p>@endif</div></div>
    <div class="fx-rev {{ ($s->settings['show_summary'] ?? true) ? '' : 'fx-rev-solo' }}">
        @if ($s->settings['show_summary'] ?? true)
        <aside class="fx-rev-sum" aria-label="{{ __('Rating summary') }}">
            <div class="fx-rev-score"><b class="figure">{{ number_format($rv['avg'], 1) }}</b><span>/ 5</span></div>
            <div class="sf-stars fx-rev-stars" aria-hidden="true">@include('store._stars', ['value' => $rv['avg']])</div>
            <p>{{ trans_choice('Based on :n review|Based on :n reviews', $rv['count'], ['n' => number_format($rv['count'])]) }}</p>
            <ul class="fx-rev-bars">
                @foreach ($rv['dist'] as $star => $n)
                    <li><span>{{ $star }}★</span><i><em style="width:{{ $rv['count'] ? round($n / $rv['count'] * 100) : 0 }}%"></em></i><small class="figure">{{ $n }}</small></li>
                @endforeach
            </ul>
            <div class="fx-rev-badges"><span><i class="bi bi-patch-check-fill"></i> {{ __('Real customers') }}</span><span><i class="bi bi-shield-check"></i> {{ __('Moderated') }}</span></div>
        </aside>
        @endif
        <div class="fx-rev-grid">
            @foreach ($list as $r)
                @php $city = $r->order?->city; $pname = $r->product?->trans('name'); @endphp
                <figure class="fx-rev-card">
                    <div class="fx-rev-top"><span class="sf-stars" role="img" aria-label="{{ $r->rating }} / 5">@include('store._stars', ['value' => $r->rating])</span><time datetime="{{ $r->created_at->toDateString() }}">{{ $r->created_at->translatedFormat('d M Y') }}</time></div>
                    @if ($r->title)<h3 dir="auto">{{ $r->title }}</h3>@endif
                    @if ($r->body)<blockquote dir="auto">{{ $r->body }}</blockquote>@endif
                    @if ($r->reply)<div class="fx-rev-reply"><i class="bi bi-arrow-return-right"></i> <b>{{ setting('store.name', config('app.name')) }}:</b> <span dir="auto">{{ $r->reply }}</span></div>@endif
                    <figcaption>
                        <span class="mk-avatar">{{ mb_strtoupper(mb_substr($r->name, 0, 1)) }}</span>
                        <div><b>{{ $r->name }}</b>
                            <small>@if ($r->order_id)<span class="fx-verified"><i class="bi bi-patch-check-fill"></i> {{ __('Verified buyer') }}</span>@endif @if ($city) · {{ $city }}@endif</small>
                            @if ($pname && $r->product?->status === 'active')<small><a href="{{ route('store.product', $r->product->slug) }}">{{ __('on :product', ['product' => $pname]) }}</a></small>@endif
                        </div>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
@endif
