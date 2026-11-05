@if (\App\Support\Integrations::recaptcha())
    <div class="mb-3">
        <div class="g-recaptcha" data-sitekey="{{ setting('integrations.recaptcha_site') }}" data-theme="{{ setting('theme.mode', 'dark') === 'light' ? 'light' : 'dark' }}"></div>
        @error('g-recaptcha-response')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    @once
        <script src="https://www.google.com/recaptcha/api.js?hl={{ app()->getLocale() === 'en' ? 'en' : (str_starts_with(app()->getLocale(), 'ur') ? 'ur' : 'en') }}" async defer nonce="{{ csp_nonce() }}"></script>
    @endonce
@endif
