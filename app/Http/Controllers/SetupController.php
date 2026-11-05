<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\DemoData;
use App\Support\BusinessProfile;
use App\Support\Installer;
use App\Support\Locales;
use App\Support\StarterData;
use Database\Seeders\RolesSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function show(): View
    {
        return view('setup.index', ['profiles' => BusinessProfile::all()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_type' => ['required', 'in:'.implode(',', array_keys(BusinessProfile::all()))],
            'store_name' => ['required', 'string', 'max:120'],
            'currency' => ['required', 'string', 'size:3'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'timezone' => ['required', 'timezone'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'confirmed', StrongPassword::rule()],
            'sample_data' => ['nullable', 'boolean'],
        ]);

        $owner = null;
        DB::transaction(function () use ($data, &$owner) {
            (new RolesSeeder)->run();

            $owner = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $owner->assignRole('owner');

            BusinessProfile::apply($data['business_type']);
            StarterData::seed($data['business_type']);

            setting()->setMany([
                'store.name' => $data['store_name'],
                'store.currency' => strtoupper($data['currency']),
                'store.currency_symbol' => $data['currency_symbol'],
                'store.timezone' => $data['timezone'],
                'store.default_locale' => app()->getLocale(),
                'store.locales' => array_keys(Locales::all()),
                'system.installed' => true,
            ]);

            if (! empty($data['sample_data'])) {
                app(DemoData::class)->load($data['business_type']);
            }
        });

        Installer::lock();

        Auth::login($owner);
        request()->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
}
