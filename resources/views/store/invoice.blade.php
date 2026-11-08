<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Invoice') }} {{ $order->number }}</title>
    <link href="{{ asset('assets/css/fonts.css') }}" rel="stylesheet">
    <style>
        body { font-family: Inter, 'Noto Naskh Arabic', Arial, sans-serif; color: #101828; background: #eef1f6; margin: 0; padding: 24px; }
        .sheet { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px -20px rgba(0,0,0,.3); }
        h1 { margin: 0; font-size: 28px; } .muted { color: #667085; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; font-size: 14px; }
        th { text-align: start; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #667085; border-bottom: 2px solid #e4e7ee; padding: 8px 6px; }
        td { padding: 10px 6px; border-bottom: 1px solid #eef0f5; vertical-align: top; } .r { text-align: end; } .num { font-family: 'JetBrains Mono', monospace; }
        .tot td { border: 0; padding: 4px 6px; } .grand td { font-size: 18px; font-weight: 700; border-top: 2px solid #101828; padding-top: 10px; }
        .row { display: flex; justify-content: space-between; gap: 24px; flex-wrap: wrap; margin-top: 28px; }
        .btn { display: inline-block; margin: 0 auto 16px; padding: 10px 18px; background: #0891b2; color: #fff; border: 0; border-radius: 10px; font: inherit; cursor: pointer; }
        .paid { display: inline-block; padding: 3px 12px; border: 2px solid #059669; color: #059669; border-radius: 8px; font-weight: 700; text-transform: uppercase; font-size: 12px; transform: rotate(-4deg); }
        @media print { body { background: #fff; padding: 0; } .sheet { box-shadow: none; padding: 0; } .noprint { display: none; } }
    </style>
</head>
<body>
<div class="noprint" style="text-align:center"><button class="btn" id="printBtn">{{ __('Print / Save as PDF') }}</button></div>
<div class="sheet">
    <div style="display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap">
        <div>@if (sf('invoice.show_logo'))<img src="{{ brand_logo() }}" alt="" style="height:44px;margin-bottom:6px"><br>@endif<h1>{{ setting('store.name') }}</h1>@if (filled(sf('invoice.tax_id')))<div class="muted">{{ __('Tax no.') }} {{ sf('invoice.tax_id') }}</div>@endif<div class="muted">{{ setting('store.email') }} @if (setting('store.phone')) · {{ setting('store.phone') }}@endif</div></div>
        <div style="text-align:end"><div class="muted">{{ __('Invoice') }}</div><div class="num" style="font-size:20px;font-weight:700">{{ $order->number }}</div><div class="muted">{{ local_time($order->placed_at, 'd M Y') }}</div>
            @if ($order->payment_status === 'paid')<div class="paid" style="margin-top:6px">{{ __('Paid') }}</div>@endif</div>
    </div>
    <div class="row">
        <div><div class="muted">{{ $order->is_pickup ? __('Pickup for') : __('Bill / ship to') }}</div><b>{{ $order->name }}</b><br>{{ $order->phone }}<br>{{ $order->email }}
            @unless ($order->is_pickup)<br>{{ $order->address_line }}@if ($order->area), {{ $order->area }}@endif<br>{{ $order->city }} {{ $order->postal_code }}@endunless</div>
        <div><div class="muted">{{ __('Payment') }}</div><b>{{ ['cod' => __('Cash on delivery'), 'bank' => __('Bank transfer'), 'stripe' => __('Card (Stripe)')][$order->payment_method] ?? $order->payment_method }}</b><br><span class="muted">{{ $order->shipping_method }}</span>@if ($order->slot_label)<br><span class="muted">{{ $order->slot_date->format('D, d M Y') }} · {{ $order->slot_label }}</span>@endif</div>
    </div>
    <table>
        <thead><tr><th>{{ __('Item') }}</th><th class="r">{{ __('Qty') }}</th><th class="r">{{ __('Price') }}</th><th class="r">{{ __('Total') }}</th></tr></thead>
        <tbody>
        @foreach ($order->items as $i)
            <tr><td><b>{{ $i->name }}</b>@if ($i->variant_name) · {{ $i->variant_name }}@endif @if ($i->sku)<div class="muted num">{{ $i->sku }}</div>@endif</td>
                <td class="r num">{{ $i->qty }}</td><td class="r num">{{ money($i->unit_price) }}</td><td class="r num">{{ money($i->total) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <table class="tot" style="width:320px;margin-inline-start:auto">
        <tr><td>{{ __('Subtotal') }}</td><td class="r num">{{ money($order->subtotal) }}</td></tr>
        @if ($order->discount > 0)<tr><td>{{ __('Discount') }} {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</td><td class="r num">−{{ money($order->discount) }}</td></tr>@endif
        <tr><td>{{ $order->shipping_method }}</td><td class="r num">{{ money($order->shipping_cost) }}</td></tr>
        @if ($order->tax > 0)<tr><td>{{ __('Tax') }} ({{ rtrim(rtrim(number_format((float) $order->tax_rate, 2), '0'), '.') }}%{{ $order->tax_inclusive ? ', '.__('included') : '' }})</td><td class="r num">{{ money($order->tax) }}</td></tr>@endif
        <tr class="grand"><td>{{ __('Total') }}</td><td class="r num">{{ money($order->total) }}</td></tr>
    </table>
    <p class="muted" style="margin-top:32px;text-align:center">{{ __('Thank you for shopping with :store.', ['store' => setting('store.name')]) }}</p>
    @if (filled(sft('invoice.footer')))<p class="muted" style="text-align:center;font-size:12px">{{ sft('invoice.footer') }}</p>@endif
</div>
<script nonce="{{ csp_nonce() }}">document.getElementById("printBtn").addEventListener("click",function(){window.print()});</script>
</body>
</html>
