<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function show(): View
    {
        $this->authorizeTo('system.manage');

        return view('admin.license.index', ['license' => License::status(true), 'enabled' => License::enabled()]);
    }

    public function activate(Request $request): RedirectResponse
    {
        $this->authorizeTo('system.manage');
        abort_unless(License::enabled(), 404);

        $key = trim((string) $request->validate(['key' => ['required', 'string', 'max:2000']])['key']);
        if (! License::verify($key)) {
            throw ValidationException::withMessages(['key' => __('That license key is not valid. Check that you copied all of it.')]);
        }

        setting()->set('system.license', $key);
        License::reset();
        $status = License::status(true);

        return redirect()->route('admin.license.show')->with($status['state'] === 'valid' ? 'success' : 'error', $status['state'] === 'valid' ? __('License activated. Thank you!') : $status['reason']);
    }

    public function remove(): RedirectResponse
    {
        $this->authorizeTo('system.manage');
        setting()->set('system.license', null);
        License::reset();

        return redirect()->route('admin.license.show')->with('success', __('License removed'));
    }
}
