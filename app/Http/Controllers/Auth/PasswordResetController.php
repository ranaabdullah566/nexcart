<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Recaptcha;
use App\Rules\StrongPassword;
use App\Support\Audit;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * "Forgot password" by e-mail for customers and staff.
 * The page never says whether an address is registered, links work once and expire, and a reset signs the account out everywhere.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot');
    }

    public function email(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190'], 'g-recaptcha-response' => Recaptcha::rules()]);
        $email = strtolower($data['email']);

        // a few links per address per hour — nobody can use us to flood someone's inbox
        $key = 'pw-reset:'.$email;
        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 3600);
            $status = Password::sendResetLink(['email' => $email]);
            Audit::log('auth.reset_requested', ['email' => $email, 'known' => $status === Password::RESET_LINK_SENT]);
        }

        return back()->with('success', __('If that e-mail belongs to an account, we have sent a link to choose a new password. It works for :m minutes.', ['m' => (int) config('auth.passwords.users.expire', 60)]));
    }

    public function show(Request $request, string $token): View
    {
        return view('auth.reset', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'confirmed', StrongPassword::rule()],
        ]);

        $status = Password::reset(
            ['email' => strtolower($data['email']), 'password' => $data['password'], 'password_confirmation' => $request->input('password_confirmation'), 'token' => $data['token']],
            function (User $user, string $password) use ($request) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();   // sign every device out
                }
                RateLimiter::clear('login:'.strtolower($user->email).'|'.$request->ip());
                event(new PasswordReset($user));
                Audit::log('auth.password_reset', [], null, $user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __('This reset link is invalid or has expired. Please ask for a new one.')]);
        }

        return redirect()->route('login')->with('success', __('Your password was changed. Sign in with the new one.'));
    }
}
