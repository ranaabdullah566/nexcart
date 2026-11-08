<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogAttribute;
use App\Rules\UniqueActive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Admin-defined product fields (composition, strength, dosage form …). */
class AttributeController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('catalog_settings.manage');

        return view('admin.attributes.index', ['attributes' => CatalogAttribute::orderBy('sort_order')->orderBy('label')->get()]);
    }

    public function create(): View
    {
        $this->authorizeTo('catalog_settings.manage');

        return view('admin.attributes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('catalog_settings.manage');
        $this->fill(new CatalogAttribute, $request);

        return redirect()->route('admin.attributes.index')->with('success', __('Attribute saved'));
    }

    public function edit(CatalogAttribute $attribute): View
    {
        $this->authorizeTo('catalog_settings.manage');

        return view('admin.attributes.edit', ['attribute' => $attribute]);
    }

    public function update(Request $request, CatalogAttribute $attribute): RedirectResponse
    {
        $this->authorizeTo('catalog_settings.manage');
        $this->fill($attribute, $request);

        return redirect()->route('admin.attributes.index')->with('success', __('Attribute saved'));
    }

    public function destroy(CatalogAttribute $attribute): RedirectResponse
    {
        $this->authorizeTo('catalog_settings.manage');
        $attribute->delete();   // values already stored on products are simply no longer shown

        return redirect()->route('admin.attributes.index')->with('success', __('Attribute deleted'));
    }

    private function fill(CatalogAttribute $attribute, Request $request): void
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'key' => ['required', 'regex:/^[a-z][a-z0-9_]{1,63}$/', new UniqueActive('catalog_attributes', 'key', $attribute->id)],
            'type' => ['required', Rule::in(CatalogAttribute::TYPES)],
            'options' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'tr' => ['nullable', 'array'],
        ]);

        $options = collect(preg_split('/\R/', (string) ($data['options'] ?? '')))->map(fn ($o) => trim($o))->filter()->unique()->values()->all();
        if ($data['type'] === 'select' && count($options) < 2) {
            throw ValidationException::withMessages(['options' => __('A select attribute needs at least two options (one per line).')]);
        }

        $attribute->fill([
            'label' => $data['label'], 'key' => $data['key'], 'type' => $data['type'],
            'options' => $data['type'] === 'select' ? $options : null,
            'business_types' => null,
            'is_required' => $request->boolean('is_required'),
            'is_filterable' => $request->boolean('is_filterable'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $attribute->setTranslationsFrom($data['tr'] ?? null, ['label']);
        $attribute->save();
    }
}
