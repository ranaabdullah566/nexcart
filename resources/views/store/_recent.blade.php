<section class="sf-section fx-recent" data-fx-recent data-url="{{ route('store.recent') }}" @isset($current) data-current="{{ $current }}" @endisset hidden data-rail>
    <div class="sf-section-head"><div><h2>{{ __('Recently viewed') }}</h2><p class="mk-sub">{{ __('Kept on this device only.') }}</p></div>
        <div class="d-flex align-items-center gap-3"><button type="button" class="fx-recent-clear" data-fx-recent-clear>{{ __('Clear') }}</button>@include('store._rail-ctrl')</div></div>
    <div class="tq-rail tq-rail-5" data-fx-recent-row data-rail-track></div>
</section>
