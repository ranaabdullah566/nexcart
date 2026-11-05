<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BusinessProfile;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()->can('settings.view'), 403);

        return view('admin.settings', [
            'modules' => $this->moduleOptions(),
            'locales' => Locales::all(),
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
            'store.default_locale' => ['required', Rule::in(array_keys(Locales::all()))],
            'store.locales' => ['required', 'array', 'min:1'],
            'store.locales.*' => [Rule::in(array_keys(Locales::all()))],
            'theme.primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme.accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme.mode' => ['required', Rule::in(['dark', 'light', 'auto'])],
        ]);

        $data['store']['currency'] = strtoupper($data['store']['currency']);
        $data['store']['tax_rate'] = (float) ($data['store']['tax_rate'] ?? 0);
        $data['store']['tax_inclusive'] = $request->boolean('store.tax_inclusive');
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

        setting()->setMany($flat);

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
