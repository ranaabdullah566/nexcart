<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<body style="margin:0;background:#f3f5fa;font-family:Arial,Helvetica,sans-serif;color:#101828">
<div style="max-width:560px;margin:0 auto;padding:24px"><div style="background:#fff;border-radius:16px;padding:28px;border:1px solid #e4e7ee">
    <h2 style="margin:0 0 8px">{{ __('You left something in your cart') }}</h2>
    <p style="color:#475467">{{ email_text('abandoned_intro', __('Your items are still waiting for you. Finish your order whenever you are ready.')) }}</p>
    <p style="color:#475467"><b>{{ trans_choice(':n item|:n items', $cart->items_count, ['n' => $cart->items_count]) }}</b> · <b>{{ money($cart->total) }}</b></p>
    <p style="margin:24px 0 0"><a href="{{ $cart->recoverUrl() }}" style="background:#0891b2;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;display:inline-block">{{ __('Return to my cart') }}</a></p>
    <p style="margin:24px 0 0;color:#98a2b3;font-size:12px">{{ __('Not interested?') }} <a href="{{ $cart->stopUrl() }}" style="color:#98a2b3">{{ __('Stop these reminders') }}</a></p>
</div></div>
</body>
</html>
