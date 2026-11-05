<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<body style="margin:0;background:#f3f5fa;font-family:Arial,Helvetica,sans-serif;color:#101828">
<div style="max-width:560px;margin:0 auto;padding:24px"><div style="background:#fff;border-radius:16px;padding:28px;border:1px solid #e4e7ee">
    @if ($order->prescription_status === 'approved')
        <h2 style="margin:0 0 8px">{{ __('Prescription approved') }}</h2>
        <p style="color:#475467">{{ __('Good news, :name — our pharmacist approved your prescription for order :number. We are preparing it now.', ['name' => $order->name, 'number' => $order->number]) }}</p>
    @else
        <h2 style="margin:0 0 8px">{{ __('Prescription needs attention') }}</h2>
        <p style="color:#475467">{{ __('Hi :name, we could not approve the prescription for order :number.', ['name' => $order->name, 'number' => $order->number]) }}</p>
        <p style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px;color:#9a3412"><b>{{ __('Reason') }}:</b> {{ $order->prescription_note }}</p>
        <p style="color:#475467">{{ __('Please upload a new, clear copy from your order page.') }}</p>
    @endif
    <p style="margin:24px 0 0"><a href="{{ $order->trackUrl() }}" style="background:#0891b2;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;display:inline-block">{{ __('Open your order') }}</a></p>
</div></div>
</body>
</html>
