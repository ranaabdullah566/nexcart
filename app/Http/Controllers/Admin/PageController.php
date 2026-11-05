<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.pages.index', ['pages' => Page::orderBy('title')->get()]);
    }

    public function create(): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $this->save(new Page, $request);

        return redirect()->route('admin.pages.index')->with('success', __('Page saved'));
    }

    public function edit(Page $page): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.pages.edit', ['page' => $page]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $this->save($page, $request);

        return redirect()->route('admin.pages.index')->with('success', __('Page saved'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $page->delete();   // menu links to it go with it

        return redirect()->route('admin.pages.index')->with('success', __('Page deleted'));
    }

    private function save(Page $page, Request $request): void
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:150'], 'body' => ['nullable', 'string', 'max:50000'], 'tr' => ['nullable', 'array'], 'meta_title' => ['nullable', 'string', 'max:120'], 'meta_description' => ['nullable', 'string', 'max:300'],]);

        $page->fill(['title' => $data['title'], 'body' => $data['body'] ?? null, 'meta_title' => $data['meta_title'] ?? null, 'meta_description' => $data['meta_description'] ?? null, 'is_published' => $request->boolean('is_published')]);
        $page->setTranslationsFrom($data['tr'] ?? null, ['title', 'body']);
        $page->save();
    }
}
