<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use App\Support\Integrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Sign in with Google" for customers. Staff accounts must use their password (+ two-factor) —
 * a Google account must never be a back door into the admin area.
 */
class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_unless(Integrations::google(), 404);

        return $this->driver()->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(Integrations::google(), 404);

        try {
            $g = $this->driver()->user();
        } catch (\Throwable) {
            return redirect()->route('login')->with('error', __('Google sign-in was cancelled or failed. Please try again.'));
        }

        $email = strtolower((string) $g->getEmail());
        if ($email === '' || ($g->user['email_verified'] ?? $g->user['verified_email'] ?? true) === false) {
            return redirect()->route('login')->with('error', __('Your Google account has no verified email.'));
        }

        $user = User::where('google_id', $g->getId())->first() ?? User::whereRaw('lower(email) = ?', [$email])->first();

        if ($user?->isStaff()) {
            return redirect()->route('login')->with('error', __('Team members sign in with their email and password.'));
        }

        if (! $user) {
            $user = User::create(['name' => $g->getName() ?: Str::before($email, '@'), 'email' => $email, 'password' => Str::random(40)]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }
        $user->forceFill(['google_id' => $g->getId()])->save();

        Auth::login($user, true);
        $request->session()->regenerate();
        Audit::log('auth.login_google', [], null, $user);

        return redirect()->intended(route('store.account'));
    }

    private function driver()
    {
        config(['services.google' => [
            'client_id' => setting('integrations.google_client_id'),
            'client_secret' => Integrations::secret('google_client_secret'),
            'redirect' => url('/auth/google/callback'),
        ]]);

        return Socialite::driver('google');
    }
}
