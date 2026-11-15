<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('categories.view');

        return view('admin.categories.index', [
            'categories' => Category::withCount('products')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeTo('categories.create');

        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('categories.create');

        $category = new Category;
        $this->fill($category, $request);

        return redirect()->route('admin.categories.index')->with('success', __('Category saved'));
    }

    public function edit(Category $category): View
    {
        $this->authorizeTo('categories.update');

        return view('admin.categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorizeTo('categories.update');
        $this->fill($category, $request);

        return redirect()->route('admin.categories.index')->with('success', __('Category saved'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorizeTo('categories.delete');

        $category->delete();   // products become uncategorised

        return redirect()->route('admin.categories.index')->with('success', __('Category deleted'));
    }

    private function fill(Category $category, Request $request): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'tr' => ['nullable', 'array'],
            'meta_title' => ['nullable', 'string', 'max:120'], 'meta_description' => ['nullable', 'string', 'max:300'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],   // no SVG: it can carry scripts
        ]);

        $category->fill([
            'name' => $data['name'],
            'parent_id' => null,   // one flat level
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'meta_title' => $data['meta_title'] ?? null, 'meta_description' => $data['meta_description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        $category->setTranslationsFrom($data['tr'] ?? null, ['name', 'description']);
        $category->save();

        if ($request->boolean('remove_image')) {
            $category->clearMediaCollection('image');
        }
        if ($request->hasFile('image')) {
            $category->addMediaFromRequest('image')->toMediaCollection('image');
        }
    }
}
