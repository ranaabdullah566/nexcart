<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\StrongPassword;
use App\Support\Audit;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Each staff member's own security: password and two-factor sign-in. Owners also set the team policy. */
class SecurityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $setup = null;
        if (! $user->two_factor_confirmed_at && ($secret = $request->session()->get('2fa.setup'))) {
            $uri = Totp::uri($secret, $user->email, (string) setting('store.name', config('app.name')));
            $setup = ['secret' => $secret, 'qr' => Totp::qrSvg($uri)];
        }

        return view('admin.security.index', [
            'user' => $user, 'setup' => $setup,
            'codes' => $request->session()->pull('2fa.codes'),
            'canPolicy' => $user->can('system.manage'),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        abort_if($request->user()->two_factor_confirmed_at, 403);
        $request->session()->put('2fa.setup', Totp::secret());

        return redirect()->route('admin.security.index');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $secret = $request->session()->get('2fa.setup');
        $request->validate(['code' => ['required', 'string', 'max:10']]);
        if (! $secret || ! Totp::verify($secret, (string) $request->input('code'))) {
            throw ValidationException::withMessages(['code' => __('That code is not valid.')]);
        }

        $codes = Totp::recoveryCodes();
        $request->user()->forceFill(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => $codes, 'two_factor_confirmed_at' => now()])->save();
        $request->session()->forget('2fa.setup');
        $request->session()->put('2fa.codes', $codes);
        Audit::log('security.2fa_enabled');

        return redirect()->route('admin.security.index')->with('success', __('Two-factor authentication is on'));
    }

    public function recovery(Request $request): RedirectResponse
    {
        $this->requirePassword($request);
        abort_unless($request->user()->two_factor_confirmed_at, 403);
        $codes = Totp::recoveryCodes();
        $request->user()->forceFill(['two_factor_recovery_codes' => $codes])->save();
        $request->session()->put('2fa.codes', $codes);
        Audit::log('security.2fa_recovery_regenerated');

        return redirect()->route('admin.security.index');
    }

    public function disable(Request $request): RedirectResponse
    {
        $this->requirePassword($request);
        $request->user()->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        Audit::log('security.2fa_disabled');

        return redirect()->route('admin.security.index')->with('success', __('Two-factor authentication is off'));
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', StrongPassword::rule()],
        ]);
        $this->requirePassword($request);
        $request->user()->update(['password' => $data['password']]);
        $request->session()->regenerate();
        Audit::log('security.password_changed');

        return back()->with('success', __('Password changed'));
    }

    public function policy(Request $request): RedirectResponse
    {
        $this->authorizeTo('system.manage');
        $data = $request->validate(['idle_minutes' => ['required', 'integer', 'min:0', 'max:1440']]);
        setting()->set('security.idle_minutes', $data['idle_minutes']);
        setting()->set('security.require_2fa', $request->boolean('require_2fa'));
        Audit::log('security.policy_changed', ['idle_minutes' => $data['idle_minutes'], 'require_2fa' => $request->boolean('require_2fa')]);

        return back()->with('success', __('Saved'));
    }

    private function requirePassword(Request $request): void
    {
        if (! Hash::check((string) $request->input('current_password'), $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => __('Your current password is not correct.')]);
        }
    }
}
