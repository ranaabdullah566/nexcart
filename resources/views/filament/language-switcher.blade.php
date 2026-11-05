@php($locales = \App\Support\Locales::all())
<div class="nx-lang" style="display:flex;gap:.25rem;align-items:center;margin-inline:.5rem">
    @foreach ($locales as $code => $meta)
        <a href="{{ route('locale', $code) }}"
           @class(['nx-lang-link', 'is-active' => app()->getLocale() === $code])
           lang="{{ str_replace('_', '-', $code) }}">{{ $meta['label'] }}</a>
    @endforeach
</div>
