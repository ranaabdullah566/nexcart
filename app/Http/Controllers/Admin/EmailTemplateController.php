<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Owner-editable wording for customer e-mails, per language. Empty = the built-in text. */
class EmailTemplateController extends Controller
{
    /** key => [label, placeholders] */
    public const FIELDS = [
        'order_intro' => ['Order confirmation — opening line', [':name', ':number']],
        'order_footer' => ['Order confirmation — closing note', [':name', ':number']],
        'status_footer' => ['Order updates — closing note', [':name', ':number']],
        'abandoned_intro' => ['Cart reminder — opening line', []],
    ];

    public function edit(): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.emails.edit', ['fields' => self::FIELDS, 'locales' => Locales::all()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');

        $request->validate(['t' => ['nullable', 'array']]);
        $flat = ['emails.status_updates' => $request->boolean('status_updates')];

        foreach (self::FIELDS as $key => $_) {
            foreach (array_keys(Locales::all()) as $locale) {
                $text = trim((string) data_get($request->input('t'), "{$key}.{$locale}", ''));
                $flat['emails.'.$key.($locale === 'en' ? '' : "_{$locale}")] = $text !== '' ? mb_substr($text, 0, 500) : null;
            }
        }
        setting()->setMany($flat);

        return redirect()->route('admin.emails.edit')->with('success', __('E-mail wording saved'));
    }
}
