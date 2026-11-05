<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Rules\UniqueActive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Shopper accounts (no staff role). Staff are created by an owner in the admin. */
class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', new UniqueActive('users', 'email', null, true)],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-()\s]{6,}$/'],
            'password' => ['required', 'string', 'confirmed', StrongPassword::rule()],
        ]);

        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('store.account'));
    }
}
