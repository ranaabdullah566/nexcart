<section class="sf-section fx-recent" data-fx-recent @isset($current) data-current="{{ $current }}" @endisset hidden>
    <div class="sf-section-head"><div><h2>{{ __('Recently viewed') }}</h2><p class="mk-sub">{{ __('Kept on this device only.') }}</p></div><button type="button" class="fx-recent-clear" data-fx-recent-clear>{{ __('Clear') }}</button></div>
    <div class="fx-recent-row" data-fx-recent-row></div>
</section>
