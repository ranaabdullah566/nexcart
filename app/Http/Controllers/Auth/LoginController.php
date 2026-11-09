<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Recaptcha;
use App\Support\Audit;
use App\Support\GuestOrders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCK_SECONDS = 900;

    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = collect($request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
            'g-recaptcha-response' => Recaptcha::rules(),
        ]))->except('g-recaptcha-response')->all();

        // lockout per account+IP, so one attacker cannot lock the real owner out from elsewhere
        $key = 'login:'.strtolower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $mins = (int) ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages(['email' => __('Too many sign-in attempts. Try again in :m minutes.', ['m' => $mins])]);
        }

        $provider = Auth::getProvider();
        $user = $provider->retrieveByCredentials($credentials);
        if (! $user || ! $provider->validateCredentials($user, $credentials)) {
            RateLimiter::hit($key, self::LOCK_SECONDS);
            Audit::log('auth.failed', ['email' => $credentials['email']]);
            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }
        RateLimiter::clear($key);

        if ($user->two_factor_confirmed_at) {
            $request->session()->regenerate();
            $request->session()->put('2fa.pending', ['id' => $user->id, 'remember' => $request->boolean('remember'), 'at' => time()]);

            return redirect()->route('login.2fa');
        }

        return $this->finish($request, $user, $request->boolean('remember'));
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        return $request->session()->has('2fa.pending') ? view('auth.two-factor') : redirect()->route('login');
    }

    public function verify(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('2fa.pending');
        if (! $pending || time() - $pending['at'] > 600 || ! ($user = User::find($pending['id']))) {
            $request->session()->forget('2fa.pending');

            return redirect()->route('login')->with('error', __('Please sign in again.'));
        }

        $request->validate(['code' => ['required', 'string', 'max:20']]);
        $key = '2fa:'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $request->session()->forget('2fa.pending');
            Audit::log('auth.2fa_locked', [], null, $user);

            return redirect()->route('login')->with('error', __('Too many attempts. Please sign in again in a few minutes.'));
        }

        if (! $user->checkTwoFactor((string) $request->input('code'))) {
            RateLimiter::hit($key, self::LOCK_SECONDS);
            Audit::log('auth.2fa_failed', [], null, $user);
            throw ValidationException::withMessages(['code' => __('That code is not valid.')]);
        }

        RateLimiter::clear($key);
        $request->session()->forget('2fa.pending');

        return $this->finish($request, $user, (bool) $pending['remember']);
    }

    private function finish(Request $request, User $user, bool $remember): RedirectResponse
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        GuestOrders::claim($user);
        $request->session()->put('nx_last_seen', time());
        Audit::log('auth.login', [], null, $user);

        return redirect()->intended($user->isStaff() ? route('admin.dashboard') : route('store.account'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Audit::log('auth.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
