<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\Locales;
use App\Support\Sf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin → Storefront: every switch, label and list the shop front uses, built from the Sf schema. */
class StorefrontController extends Controller
{
    public function edit(): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.storefront.edit', ['schema' => Sf::schema(), 'groups' => Sf::GROUPS, 'locales' => Locales::enabled() + ['en' => ['label' => 'English', 'dir' => 'ltr']]]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $schema = Sf::schema();
        $codes = array_keys(Locales::all());

        $rules = [];
        foreach ($schema as $key => $def) {
            $f = str_replace('.', '_', $key);
            $rules["v.$f"] = match ($def['type']) {
                'int' => ['nullable', 'integer', 'min:'.($def['min'] ?? 0), 'max:'.($def['max'] ?? 999)],
                'select' => ['nullable', Rule::in(array_map('strval', array_keys($def['options'])))],
                'string' => ['nullable', 'string', 'max:'.($def['max'] ?? 190)],
                'text' => ['nullable', 'array'],
                'lines' => ['nullable', 'array'],
                default => ['nullable'],
            };
            if (in_array($def['type'], ['text', 'lines'], true)) {
                $rules["v.$f.*"] = ['nullable', 'string', 'max:'.($def['type'] === 'lines' ? 1500 : 400)];
            }
        }
        $data = $request->validate($rules);

        $changed = 0;
        foreach ($schema as $key => $def) {
            $f = str_replace('.', '_', $key);
            $raw = $request->input("v.$f");
            switch ($def['type']) {
                case 'bool':
                    setting()->set('sf.'.$key, $request->boolean("v.$f"));
                    break;
                case 'int':
                    setting()->set('sf.'.$key, filled($raw) ? (int) $raw : $def['default']);
                    break;
                case 'select':
                case 'string':
                    setting()->set('sf.'.$key, filled($raw) ? (string) $raw : $def['default']);
                    break;
                default:   // text / lines: one value per language
                    $vals = [];
                    foreach ((array) $raw as $loc => $val) {
                        if (in_array($loc, $codes, true) && filled(trim((string) $val))) {
                            $vals[$loc] = trim(strip_tags((string) $val));
                        }
                    }
                    setting()->set('sft.'.$key, $vals);
            }
            $changed++;
        }
        Audit::log('storefront.updated', ['options' => $changed]);

        return redirect()->route('admin.storefront.edit', ['tab' => $request->input('tab')])->with('success', __('Storefront saved'));
    }
}
