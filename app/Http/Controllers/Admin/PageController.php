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
        $data = $request->validate(['title' => ['required', 'string', 'max:150'], 'body' => ['nullable', 'string', 'max:50000'], 'tr' => ['nullable', 'array']]);

        $page->fill(['title' => $data['title'], 'body' => $data['body'] ?? null, 'is_published' => $request->boolean('is_published')]);
        $page->setTranslationsFrom($data['tr'] ?? null, ['title', 'body']);
        $page->save();
    }
}
