<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\Locales;
use App\Support\UiText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin → Interface text: change any word the shop and admin show, per language. */
class UiTextController extends Controller
{
    private const PER_PAGE = 40;

    public function index(Request $request): View
    {
        $this->authorizeTo('storefront.manage');
        $locales = Locales::all();
        $loc = array_key_exists($request->query('locale'), $locales) ? $request->query('locale') : (Locales::enabled() ? array_key_first(Locales::enabled()) : 'en');
        $q = trim((string) $request->query('q'));

        $keys = collect(UiText::keys())->when($q !== '', fn ($c) => $c->filter(fn ($k) => stripos($k, $q) !== false || stripos((string) (UiText::builtin($loc)[$k] ?? ''), $q) !== false || stripos((string) (UiText::overrides($loc)[$k] ?? ''), $q) !== false))->values();
        $page = max(1, (int) $request->query('page', 1));
        $slice = new LengthAwarePaginator($keys->forPage($page, self::PER_PAGE)->values(), $keys->count(), self::PER_PAGE, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('admin.texts.index', ['rows' => $slice, 'locale' => $loc, 'locales' => $locales, 'q' => $q, 'builtin' => UiText::builtin($loc), 'over' => UiText::overrides($loc), 'overCount' => count(UiText::overrides($loc))]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $data = $request->validate([
            'locale' => ['required', Rule::in(array_keys(Locales::all()))],
            'keys' => ['nullable', 'array', 'max:'.self::PER_PAGE], 'keys.*' => ['string', 'max:300'],
            'vals' => ['nullable', 'array', 'max:'.self::PER_PAGE], 'vals.*' => ['nullable', 'string', 'max:400'],
        ]);
        $loc = $data['locale'];
        $valid = array_flip(UiText::keys());
        $builtin = UiText::builtin($loc);
        $map = UiText::overrides($loc);

        foreach ((array) ($data['keys'] ?? []) as $i => $key) {
            if (! isset($valid[$key])) {
                continue;   // only real interface strings can be overridden
            }
            $val = trim(strip_tags((string) ($data['vals'][$i] ?? '')));
            $base = $builtin[$key] ?? $key;
            if ($val === '' || $val === $base) {
                unset($map[$key]);   // empty or unchanged: back to the built-in wording
            } else {
                $map[$key] = $val;
            }
        }
        UiText::save($loc, $map);
        Audit::log('texts.updated', ['locale' => $loc, 'overrides' => count($map)]);

        return redirect()->route('admin.texts.index', array_filter(['locale' => $loc, 'q' => $request->input('q'), 'page' => $request->input('page')]))->with('success', __('Interface text saved'));
    }

    public function reset(Request $request): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $loc = $request->validate(['locale' => ['required', Rule::in(array_keys(Locales::all()))]])['locale'];
        UiText::save($loc, []);
        Audit::log('texts.reset', ['locale' => $loc]);

        return redirect()->route('admin.texts.index', ['locale' => $loc])->with('success', __('Back to the built-in wording'));
    }
}
