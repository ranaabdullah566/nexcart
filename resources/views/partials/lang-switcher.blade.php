<nav class="nx-lang" aria-label="{{ __('Language') }}">
    @foreach (\App\Support\Locales::enabled() as $code => $meta)
        <a href="{{ route('locale', $code) }}" lang="{{ str_replace('_', '-', $code) }}" @class(['on' => app()->getLocale() === $code])>{{ $meta['label'] }}</a>
    @endforeach
</nav>
