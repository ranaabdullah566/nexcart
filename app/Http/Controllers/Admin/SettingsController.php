<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Payments\StripeCheckout;
use App\Support\BusinessProfile;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()->can('settings.view'), 403);

        return view('admin.settings', [
            'modules' => $this->moduleOptions(),
            'locales' => Locales::all(),
            'hasStripeKey' => (new StripeCheckout)->secret() !== null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings.update'), 403);

        $data = $request->validate([
            'store.name' => ['required', 'string', 'max:120'],
            'store.tagline' => ['nullable', 'string', 'max:190'],
            'store.email' => ['nullable', 'email', 'max:190'],
            'store.phone' => ['nullable', 'string', 'max:40'],
            'store.currency' => ['required', 'string', 'size:3'],
            'store.currency_symbol' => ['required', 'string', 'max:5'],
            'store.timezone' => ['required', 'timezone'],
            'store.tax_rate' => ['nullable', 'numeric', 'between:0,100'],
            'store.whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s()-]{6,}$/'],
            'store.facebook' => ['nullable', 'url', 'max:190'], 'store.instagram' => ['nullable', 'url', 'max:190'], 'store.tiktok' => ['nullable', 'url', 'max:190'],
            'store.x' => ['nullable', 'url', 'max:190'], 'store.youtube' => ['nullable', 'url', 'max:190'], 'store.linkedin' => ['nullable', 'url', 'max:190'],
            'store.phone_code' => ['nullable', 'regex:/^[0-9]{1,4}$/'],
            'growth.earn_per' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'growth.point_value' => ['nullable', 'numeric', 'min:0.01', 'max:100000'],
            'growth.min_redeem' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'growth.abandoned_hours' => ['nullable', 'integer', 'min:1', 'max:72'],
            'store.default_locale' => ['required', Rule::in(array_keys(Locales::all()))],
            'store.locales' => ['required', 'array', 'min:1'],
            'store.locales.*' => [Rule::in(array_keys(Locales::all()))],
            'theme.primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme.accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme.mode' => ['required', Rule::in(['dark', 'light', 'auto'])],
            'payments.bank_instructions' => ['nullable', 'string', 'max:1000'],
            'payments.stripe_secret' => ['nullable', 'string', 'max:200', 'regex:/^(sk|rk)_(test|live)_[A-Za-z0-9]+$/'],
        ]);

        $bankOn = $request->boolean('payments.bank');
        $stripeOn = $request->boolean('payments.stripe');
        if ($bankOn && blank($data['payments']['bank_instructions'] ?? null)) {
            throw ValidationException::withMessages(['payments.bank_instructions' => __('Add your bank details so customers know where to pay.')]);
        }
        if ($stripeOn && blank($data['payments']['stripe_secret'] ?? null) && ! (new StripeCheckout)->secret()) {
            throw ValidationException::withMessages(['payments.stripe_secret' => __('Enter your Stripe secret key to turn on card payments.')]);
        }

        $data['store']['currency'] = strtoupper($data['store']['currency']);
        $data['store']['tax_rate'] = (float) ($data['store']['tax_rate'] ?? 0);
        $data['store']['tax_inclusive'] = $request->boolean('store.tax_inclusive');
        $data['store']['phone_code'] = $data['store']['phone_code'] ?? '92';
        // the default language must always be one of the enabled languages
        $data['store']['locales'] = array_values(array_unique([...$data['store']['locales'], $data['store']['default_locale']]));

        $flat = [];
        foreach (['store', 'theme'] as $group) {
            foreach ($data[$group] as $key => $value) {
                $flat["{$group}.{$key}"] = $value;
            }
        }
        foreach (array_keys($this->moduleOptions()) as $module) {
            $flat["modules.{$module}"] = $request->boolean("modules.{$module}");
        }

        setting()->setMany($flat + [
            'growth.loyalty' => $request->boolean('growth.loyalty'),
            'growth.earn_per' => (float) ($data['growth']['earn_per'] ?? 100),
            'growth.point_value' => (float) ($data['growth']['point_value'] ?? 1),
            'growth.min_redeem' => (int) ($data['growth']['min_redeem'] ?? 50),
            'growth.abandoned' => $request->boolean('growth.abandoned'),
            'growth.abandoned_hours' => (int) ($data['growth']['abandoned_hours'] ?? 2),
            'growth.reviews_auto' => $request->boolean('growth.reviews_auto'),
            'payments.cod' => $request->boolean('payments.cod'),
            'payments.bank' => $bankOn,
            'payments.bank_instructions' => $data['payments']['bank_instructions'] ?? null,
            'payments.stripe' => $stripeOn,
        ]);
        if (filled($data['payments']['stripe_secret'] ?? null)) {
            StripeCheckout::storeSecret($data['payments']['stripe_secret']);   // blank keeps the saved key
        }

        return redirect()->route('admin.settings.edit')->with('success', __('Settings saved'));
    }

    /** @return array<string, string> */
    private function moduleOptions(): array
    {
        return collect(BusinessProfile::all())
            ->pluck('modules')->flatten()->unique()->sort()
            ->mapWithKeys(fn ($m) => [$m => str($m)->replace('_', ' ')->title()->toString()])
            ->all();
    }
}
