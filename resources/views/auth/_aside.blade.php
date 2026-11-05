{{-- brand panel shown beside the sign-in / sign-up forms --}}
<aside class="mk-auth-aside">
    <a href="{{ route('store.home') }}" class="sf-brand"><span class="sf-brand-logo"><img src="{{ brand_logo() }}" alt=""></span><span class="sf-brand-name">{{ setting('store.name') }}</span></a>
    <h2>{{ $headline }}</h2>
    <p>{{ $sub }}</p>
    <ul>
        <li><i class="bi bi-truck"></i><div><b>{{ __('Track every order') }}</b><span>{{ __('See each step from packing to your door.') }}</span></div></li>
        <li><i class="bi bi-lightning-charge"></i><div><b>{{ __('Faster checkout') }}</b><span>{{ __('Your details are saved for next time.') }}</span></div></li>
        <li><i class="bi bi-heart"></i><div><b>{{ __('Wishlist & compare') }}</b><span>{{ __('Keep what you like and decide later.') }}</span></div></li>
    </ul>
</aside>
