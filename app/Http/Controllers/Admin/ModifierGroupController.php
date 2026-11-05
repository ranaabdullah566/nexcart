<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModifierGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Add-on groups ("Choose size", "Extra toppings") with priced options. */
class ModifierGroupController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('catalog_settings.manage');

        return view('admin.modifiers.index', ['groups' => ModifierGroup::with('options')->withCount('products')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        $this->authorizeTo('catalog_settings.manage');

        return view('admin.modifiers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('catalog_settings.manage');
        $this->save(new ModifierGroup, $request);

        return redirect()->route('admin.modifier-groups.index')->with('success', __('Add-on group saved'));
    }

    public function edit(ModifierGroup $modifierGroup): View
    {
        $this->authorizeTo('catalog_settings.manage');

        return view('admin.modifiers.edit', ['group' => $modifierGroup->load('options')]);
    }

    public function update(Request $request, ModifierGroup $modifierGroup): RedirectResponse
    {
        $this->authorizeTo('catalog_settings.manage');
        $this->save($modifierGroup, $request);

        return redirect()->route('admin.modifier-groups.index')->with('success', __('Add-on group saved'));
    }

    public function destroy(ModifierGroup $modifierGroup): RedirectResponse
    {
        $this->authorizeTo('catalog_settings.manage');
        $modifierGroup->delete();

        return redirect()->route('admin.modifier-groups.index')->with('success', __('Add-on group deleted'));
    }

    private function save(ModifierGroup $group, Request $request): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'min_select' => ['required', 'integer', 'min:0', 'max:20'],
            'max_select' => ['required', 'integer', 'min:1', 'max:20', 'gte:min_select'],
            'options' => ['required', 'array', 'min:1', 'max:50'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.name' => ['required', 'string', 'max:120'],
            'options.*.price_delta' => ['nullable', 'numeric', 'between:-99999,99999'],
            'tr' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($group, $data, $request) {
            $group->fill(['name' => $data['name'], 'min_select' => $data['min_select'], 'max_select' => $data['max_select'], 'is_active' => $request->boolean('is_active')]);
            $group->setTranslationsFrom($data['tr'] ?? null, ['name']);
            $group->save();

            $keep = [];
            foreach (array_values($data['options']) as $i => $row) {
                $option = ($row['id'] ?? null) ? $group->options()->find($row['id']) : null;
                $option ??= $group->options()->make();
                $option->fill([
                    'name' => $row['name'], 'price_delta' => $row['price_delta'] ?? 0, 'sort_order' => $i,
                    'is_default' => (bool) ($row['is_default'] ?? false), 'is_active' => true,
                ])->save();
                $keep[] = $option->id;
            }
            $group->options()->whereNotIn('id', $keep)->delete();
        });
    }
}
