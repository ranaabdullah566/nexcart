<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        return view('store.account', ['orders' => Order::where('user_id', $request->user()->id)->latest('id')->get(), 'user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-()\s]{6,}$/'],
            'address_line' => ['nullable', 'string', 'max:255'], 'city' => ['nullable', 'string', 'max:120'],
            'area' => ['nullable', 'string', 'max:120'], 'postal_code' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->fill([
            'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null,
            'default_address' => array_filter(collect($data)->only(['address_line', 'city', 'area', 'postal_code'])->all()) ?: null,
        ]);
        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }
        $user->save();

        return back()->with('success', __('Profile saved'));
    }
}
