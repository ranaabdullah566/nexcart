<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('categories.view');

        return view('admin.categories.index', [
            'categories' => Category::with('parent.parent.parent')->withCount('products')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeTo('categories.create');

        return view('admin.categories.create', ['parents' => $this->parentOptions(), 'preParent' => request('parent')]);
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

        return view('admin.categories.edit', ['category' => $category, 'parents' => $this->parentOptions($category)]);
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

        $category->delete();   // children move to top level, products become uncategorised

        return redirect()->route('admin.categories.index')->with('success', __('Category deleted'));
    }

    private function fill(Category $category, Request $request): void
    {
        $forbidden = $category->exists ? $category->descendantIds() : [];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'exists:categories,id', Rule::notIn($forbidden)],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'tr' => ['nullable', 'array'],
            'meta_title' => ['nullable', 'string', 'max:120'], 'meta_description' => ['nullable', 'string', 'max:300'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],   // no SVG: it can carry scripts
        ]);

        if (! empty($data['parent_id'])) {
            $parent = Category::find($data['parent_id']);
            $height = $category->exists ? $category->height() : 1;
            if ($parent->depth() + $height > Category::MAX_DEPTH) {
                throw ValidationException::withMessages(['parent_id' => __('Categories can only be :n levels deep (category › sub-category › sub-sub-category).', ['n' => Category::MAX_DEPTH])]);
            }
        }

        $category->fill([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
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

    /** Every category except the one being edited and its descendants (no loops). */
    private function parentOptions(?Category $editing = null)
    {
        $skip = $editing ? $editing->descendantIds() : [];
        $height = $editing ? $editing->height() : 1;

        // a parent is offered only if the new branch still fits in three levels
        return Category::with('parent.parent.parent')->whereNotIn('id', $skip)->orderBy('name')->get()
            ->filter(fn ($c) => $c->depth() + $height <= Category::MAX_DEPTH)
            ->sortBy(fn ($c) => $c->path)->values();
    }
}
