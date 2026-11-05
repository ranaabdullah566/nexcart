<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Set up your store') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Noto+Naskh+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    <script>try{var t=localStorage.getItem('nx-theme')||(matchMedia('(prefers-color-scheme: light)').matches?'light':'dark');document.documentElement.dataset.theme=t}catch(e){document.documentElement.dataset.theme='dark'}</script>
    <style>
        :root { --bg:#07070d; --card:rgb(255 255 255 / .04); --line:rgb(255 255 255 / .09); --fg:#eef0ff; --muted:#9aa0c3; --p:#7c3aed; --a:#22d3ee; }
        :root[data-theme="light"] { --bg:#f5f6fb; --card:rgb(255 255 255 / .85); --line:rgb(15 15 40 / .12); --fg:#17163a; --muted:#5d6188; }
        :root[data-theme="light"] body { background: radial-gradient(60rem 40rem at 10% -10%, rgb(124 58 237 / .16), transparent 60%), radial-gradient(50rem 35rem at 100% 0, rgb(34 211 238 / .16), transparent 60%), var(--bg); }
        :root[data-theme="light"] input, :root[data-theme="light"] select { background:#fff; }
        :root[data-theme="light"] h1 { background:linear-gradient(90deg,#1e1b4b,#6d28d9 60%,#0891b2); -webkit-background-clip:text; background-clip:text; }
        :root[data-theme="light"] legend { color:#0e7490; }
        [dir="rtl"] body { font-family:'Noto Naskh Arabic',system-ui,sans-serif; }
        [dir="rtl"] h1 { letter-spacing:0; }
        .bar { max-width:760px; margin:0 auto 18px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
        .langs { display:flex; gap:6px; flex-wrap:wrap; }
        .langs a, .tgl { padding:6px 12px; border-radius:999px; border:1px solid var(--line); color:var(--muted); text-decoration:none; font-size:.82rem; font-weight:600; background:var(--card); cursor:pointer; font-family:inherit; }
        .langs a.on { color:#fff; border-color:transparent; background:linear-gradient(135deg,var(--p),#4c1d95); }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; font-family:'Space Grotesk',system-ui,sans-serif; color:var(--fg); padding:32px 16px 64px;
            background: radial-gradient(60rem 40rem at 10% -10%, rgb(124 58 237 / .3), transparent 60%), radial-gradient(50rem 35rem at 100% 0, rgb(34 211 238 / .18), transparent 60%), var(--bg); }
        .wrap { max-width: 760px; margin: 0 auto; }
        h1 { font-size: clamp(1.8rem, 4vw, 2.6rem); letter-spacing:-.03em; margin:.2em 0; background:linear-gradient(90deg,#fff,#c4b5fd 60%,var(--a)); -webkit-background-clip:text; background-clip:text; color:transparent; }
        p.lead { color:var(--muted); margin:0 0 28px; }
        fieldset { border:1px solid var(--line); background:var(--card); backdrop-filter:blur(14px); border-radius:18px; padding:22px; margin:0 0 20px; }
        legend { padding:0 8px; font-weight:700; color:var(--a); }
        .grid { display:grid; gap:14px; grid-template-columns: 1fr 1fr; }
        @media (max-width:600px){ .grid{grid-template-columns:1fr;} }
        label.f { display:block; font-size:.85rem; color:var(--muted); }
        input, select { width:100%; margin-top:6px; padding:12px 14px; border-radius:12px; border:1px solid var(--line); background:rgb(0 0 0 / .35); color:var(--fg); font:inherit; }
        input:focus, select:focus { outline:2px solid var(--p); border-color:transparent; }
        .types { display:grid; gap:12px; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); }
        .type input { position:absolute; opacity:0; }
        .type span { display:block; height:100%; padding:16px; border-radius:14px; border:1px solid var(--line); cursor:pointer; transition:.2s; }
        .type b { display:block; margin-bottom:4px; }
        .type small { color:var(--muted); }
        .type input:checked + span { border-color:var(--p); background:rgb(124 58 237 / .16); box-shadow:0 0 30px -8px var(--p); }
        .type input:focus-visible + span { outline:2px solid var(--a); }
        button { width:100%; padding:15px; border:0; border-radius:14px; font:inherit; font-weight:700; color:#fff; cursor:pointer;
            background:linear-gradient(135deg,var(--p),#4c1d95); box-shadow:0 12px 36px -8px rgb(124 58 237 / .7); }
        .err { color:#fca5a5; font-size:.8rem; margin-top:4px; }
    </style>
</head>
<body>
<div class="bar">
    <nav class="langs" aria-label="Language">
        @foreach (\App\Support\Locales::all() as $code => $meta)
            <a href="{{ route('locale', $code) }}" lang="{{ str_replace('_', '-', $code) }}" @class(['on' => app()->getLocale() === $code])>{{ $meta['label'] }}</a>
        @endforeach
    </nav>
    <button type="button" class="tgl" id="nx-theme" aria-label="{{ __('Toggle light/dark mode') }}">◐ {{ __('Light / Dark') }}</button>
</div>
<main class="wrap">
    <h1>{{ __('Let’s set up your store') }}</h1>
    <p class="lead">{{ __('Pick your business type — modules, checkout and theme are preconfigured. You can change everything later from the admin panel.') }}</p>

    <form method="POST" action="{{ route('setup.store') }}">
        @csrf

        <fieldset>
            <legend>{{ __('1 · Business type') }}</legend>
            <div class="types">
                @foreach ($profiles as $key => $profile)
                    <label class="type">
                        <input type="radio" name="business_type" value="{{ $key }}" @checked(old('business_type', 'general') === $key)>
                        <span><b>{{ __($profile['label']) }}</b><small>{{ __($profile['description']) }}</small></span>
                    </label>
                @endforeach
            </div>
            @error('business_type')<div class="err">{{ $message }}</div>@enderror
        </fieldset>

        <fieldset>
            <legend>{{ __('2 · Store') }}</legend>
            <div class="grid">
                <label class="f">{{ __('Store name') }}<input name="store_name" value="{{ old('store_name') }}" required>@error('store_name')<div class="err">{{ $message }}</div>@enderror</label>
                <label class="f">{{ __('Timezone') }}<input name="timezone" value="{{ old('timezone', 'Asia/Karachi') }}" required>@error('timezone')<div class="err">{{ $message }}</div>@enderror</label>
                <label class="f">{{ __('Currency code') }}<input name="currency" value="{{ old('currency', 'PKR') }}" maxlength="3" required>@error('currency')<div class="err">{{ $message }}</div>@enderror</label>
                <label class="f">{{ __('Currency symbol') }}<input name="currency_symbol" value="{{ old('currency_symbol', 'Rs') }}" maxlength="5" required>@error('currency_symbol')<div class="err">{{ $message }}</div>@enderror</label>
            </div>
        </fieldset>

        <fieldset>
            <legend>{{ __('3 · Owner account') }}</legend>
            <div class="grid">
                <label class="f">{{ __('Your name') }}<input name="name" value="{{ old('name') }}" required>@error('name')<div class="err">{{ $message }}</div>@enderror</label>
                <label class="f">{{ __('Email') }}<input type="email" name="email" value="{{ old('email') }}" required>@error('email')<div class="err">{{ $message }}</div>@enderror</label>
                <label class="f">{{ __('Password') }}<input type="password" name="password" minlength="8" required>@error('password')<div class="err">{{ $message }}</div>@enderror</label>
                <label class="f">{{ __('Confirm password') }}<input type="password" name="password_confirmation" minlength="8" required></label>
            </div>
        </fieldset>

        <button type="submit">{{ __('Launch my store') }} →</button>
    </form>
</main>
<script>
document.getElementById('nx-theme').addEventListener('click',function(){var r=document.documentElement,n=r.dataset.theme==='light'?'dark':'light';r.dataset.theme=n;try{localStorage.setItem('nx-theme',n)}catch(e){}});
</script>
</body>
</html>
