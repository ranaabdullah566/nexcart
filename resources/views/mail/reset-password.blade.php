<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<body style="margin:0;background:#f3f5fa;font-family:Arial,Helvetica,sans-serif;color:#101828">
<div style="max-width:560px;margin:0 auto;padding:24px"><div style="background:#fff;border-radius:16px;padding:28px;border:1px solid #e4e7ee">
    <h2 style="margin:0 0 8px">{{ __('Reset your password') }}</h2>
    <p style="color:#475467">{{ __('Hi :name,', ['name' => $user->name]) }} {{ __('we received a request to choose a new password for your :store account.', ['store' => setting('store.name')]) }}</p>
    <p style="margin:24px 0"><a href="{{ $url }}" style="background:#0891b2;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;display:inline-block">{{ __('Choose a new password') }}</a></p>
    <p style="color:#667085;font-size:13px">{{ __('This link works once and expires in :m minutes.', ['m' => $minutes]) }} {{ __('If you did not ask for this, you can ignore this e-mail — your password stays the same.') }}</p>
    <p style="color:#98a2b3;font-size:12px;word-break:break-all">{{ __('Button not working? Copy this address into your browser:') }}<br>{{ $url }}</p>
</div></div>
</body>
</html>
