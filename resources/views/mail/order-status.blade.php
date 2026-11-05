<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<body style="margin:0;background:#f3f5fa;font-family:Arial,Helvetica,sans-serif;color:#101828">
<div style="max-width:560px;margin:0 auto;padding:24px"><div style="background:#fff;border-radius:16px;padding:28px;border:1px solid #e4e7ee">
    @php
        $messages = [
            'confirmed' => __('Your order :number is confirmed — we will start preparing it shortly.'),
            'ready' => $order->is_pickup ? __('Your order :number is ready for pickup.') : __('Your order :number is packed and waiting for our rider.'),
            'out_for_delivery' => __('Your order :number is on its way to you.'),
            'completed' => __('Your order :number has been completed. Thank you for shopping with us!'),
            'cancelled' => __('Your order :number was cancelled.'),
        ];
    @endphp
    <h2 style="margin:0 0 8px">{{ $order->statusLabel() }}</h2>
    <p style="color:#475467">{{ __('Hi :name,', ['name' => $order->name]) }} {{ str_replace(':number', $order->number, $messages[$order->status] ?? '') }}</p>
    @if ($note)<p style="background:#f2f4f7;border-radius:10px;padding:12px;color:#344054">{{ $note }}</p>@endif
    @if ($order->eta_at && $order->eta_at->isFuture() && in_array($order->status, ['confirmed', 'ready', 'out_for_delivery'], true))
        <p style="color:#475467"><b>{{ __('Estimated arrival') }}:</b> {{ $order->eta_at->timezone(setting('store.timezone'))->format('h:i A') }}</p>
    @endif
    <p style="color:#667085;font-size:13px">{{ email_text('status_footer', '', ['name' => $order->name, 'number' => $order->number]) }}</p>
    <p style="margin:24px 0 0"><a href="{{ $order->trackUrl() }}" style="background:#0891b2;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;display:inline-block">{{ __('Track your order') }}</a></p>
</div></div>
</body>
</html>
