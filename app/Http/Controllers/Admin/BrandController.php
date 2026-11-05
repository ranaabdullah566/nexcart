<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('products.view');

        return view('admin.brands.index', ['brands' => Brand::withCount('products')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        $this->authorizeTo('products.create');

        return view('admin.brands.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('products.create');
        $this->fill(new Brand, $request);

        return redirect()->route('admin.brands.index')->with('success', __('Brand saved'));
    }

    public function edit(Brand $brand): View
    {
        $this->authorizeTo('products.update');

        return view('admin.brands.edit', ['brand' => $brand]);
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $this->authorizeTo('products.update');
        $this->fill($brand, $request);

        return redirect()->route('admin.brands.index')->with('success', __('Brand saved'));
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorizeTo('products.delete');
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', __('Brand deleted'));
    }

    private function fill(Brand $brand, Request $request): void
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'tr' => ['nullable', 'array']]);

        $brand->fill(['name' => $data['name'], 'is_active' => $request->boolean('is_active')]);
        $brand->setTranslationsFrom($data['tr'] ?? null, ['name']);
        $brand->save();
    }
}
