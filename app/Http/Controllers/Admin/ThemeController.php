<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Colours, shape, fonts, logo and announcement bar — with a live preview of the real storefront next to the form. */
class ThemeController extends Controller
{
    public const RADII = ['0.25rem' => 'Sharp', '0.5rem' => 'Soft', '1rem' => 'Rounded', '1.5rem' => 'Very rounded'];

    public function edit(): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.theme.edit', ['radii' => self::RADII, 'locales' => collect(Locales::all())->except('en')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');

        $data = $request->validate([
            'primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'mode' => ['required', Rule::in(['dark', 'light', 'auto'])],
            'radius' => ['required', Rule::in(array_keys(self::RADII))],
            'font' => ['required', Rule::in(['space', 'inter'])],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],      // no SVG: it can carry scripts
            'favicon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,ico', 'max:512'],
            'announce_text' => ['nullable', 'string', 'max:160'],
            'announce_url' => ['nullable', 'string', 'max:300', 'regex:/^(\/|https?:\/\/)/'],
            'tr' => ['nullable', 'array'],
        ]);

        $flat = [
            'theme.primary' => $data['primary'], 'theme.accent' => $data['accent'], 'theme.mode' => $data['mode'],
            'theme.radius' => $data['radius'], 'theme.font' => $data['font'],
            'announce.enabled' => $request->boolean('announce_enabled'), 'announce.text' => $data['announce_text'] ?? null, 'announce.url' => $data['announce_url'] ?? null,
        ];
        foreach (array_keys(Locales::all()) as $locale) {
            if ($locale !== 'en') {
                $flat["announce.text_{$locale}"] = mb_substr(trim((string) data_get($data, "tr.{$locale}.announce_text", '')), 0, 160) ?: null;
            }
        }

        foreach (['logo' => 'brand.logo', 'favicon' => 'brand.favicon'] as $field => $key) {
            if ($request->boolean("remove_{$field}")) {
                $this->forget($key);
                $flat[$key] = null;
            }
            if ($request->hasFile($field)) {
                $this->forget($key);
                $flat[$key] = $request->file($field)->store('branding', 'public');
            }
        }

        setting()->setMany($flat);

        return redirect()->route('admin.theme.edit')->with('success', __('Theme saved'));
    }

    private function forget(string $key): void
    {
        if ($old = setting($key)) {
            Storage::disk('public')->delete($old);
        }
    }
}
