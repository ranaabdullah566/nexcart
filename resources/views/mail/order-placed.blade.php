<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<body style="margin:0;background:#f3f5fa;font-family:Arial,Helvetica,sans-serif;color:#101828">
<div style="max-width:600px;margin:0 auto;padding:24px">
    <div style="background:#fff;border-radius:16px;padding:28px;border:1px solid #e4e7ee">
        <h2 style="margin:0 0 4px">{{ $forStore ? __('New order :number', ['number' => $order->number]) : __('Thank you, :name!', ['name' => $order->name]) }}</h2>
        <p style="color:#667085;margin:0 0 20px">{{ $forStore ? __('A customer just placed an order.') : email_text('order_intro', __('We received your order :number and will keep you posted.'), ['number' => $order->number, 'name' => $order->name]) }}</p>

        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:14px">
            @foreach ($order->items as $i)
                <tr style="border-bottom:1px solid #eef0f5">
                    <td>{{ $i->qty }} × {{ $i->name }}@if ($i->variant_name) · {{ $i->variant_name }}@endif</td>
                    <td align="right">{{ money($i->total) }}</td>
                </tr>
            @endforeach
            <tr><td align="right" style="color:#667085">{{ __('Subtotal') }}</td><td align="right">{{ money($order->subtotal) }}</td></tr>
            @if ($order->discount > 0)<tr><td align="right" style="color:#667085">{{ __('Discount') }}</td><td align="right">−{{ money($order->discount) }}</td></tr>@endif
            <tr><td align="right" style="color:#667085">{{ $order->shipping_method }}</td><td align="right">{{ money($order->shipping_cost) }}</td></tr>
            @if ($order->tax > 0)<tr><td align="right" style="color:#667085">{{ __('Tax') }}@if ($order->tax_inclusive) ({{ __('included') }})@endif</td><td align="right">{{ money($order->tax) }}</td></tr>@endif
            <tr><td align="right"><b>{{ __('Total') }}</b></td><td align="right"><b>{{ money($order->total) }}</b></td></tr>
        </table>

        <p style="margin:20px 0 4px"><b>{{ $order->is_pickup ? __('Pickup') : __('Delivery to') }}</b></p>
        <p style="margin:0;color:#475467">{{ $order->name }} · {{ $order->phone }}<br>@unless ($order->is_pickup){{ $order->address_line }}, {{ $order->city }}@endunless</p>

        @if ($order->slot_label)<p style="margin:12px 0 0;color:#475467"><b>{{ __('Time') }}:</b> {{ $order->slot_date->format('D, d M') }} · {{ $order->slot_label }}</p>@endif
        @if ($order->eta_at && $order->eta_at->isFuture())<p style="margin:12px 0 0;color:#475467"><b>{{ __('Estimated arrival') }}:</b> {{ $order->eta_at->timezone(setting('store.timezone'))->format('h:i A') }}</p>@endif

        <p style="margin:20px 0 4px"><b>{{ __('Payment') }}</b></p>
        <p style="margin:0;color:#475467">{{ $order->payment_method === 'bank' ? __('Bank transfer') : ($order->payment_method === 'stripe' ? __('Card (Stripe)') : __('Cash on delivery')) }}</p>

        @unless ($forStore)
            @if (email_text('order_footer', ''))<p style="margin:20px 0 0;color:#667085;font-size:13px">{{ email_text('order_footer', '', ['number' => $order->number, 'name' => $order->name]) }}</p>@endif
            <p style="margin:24px 0 0"><a href="{{ $order->trackUrl() }}" style="background:#0891b2;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;display:inline-block">{{ __('Track your order') }}</a></p>
        @else
            <p style="margin:24px 0 0"><a href="{{ route('admin.orders.index') }}" style="background:#0891b2;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;display:inline-block">{{ __('Open in admin') }}</a></p>
        @endunless
    </div>
</div>
</body>
</html>
