<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.articles.index', ['articles' => Article::orderByDesc('published_at')->orderByDesc('id')->get()]);
    }

    public function create(): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.articles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $this->save(new Article, $request);

        return redirect()->route('admin.articles.index')->with('success', __('Article saved'));
    }

    public function edit(Article $article): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.articles.edit', ['article' => $article]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $this->save($article, $request);

        return redirect()->route('admin.articles.index')->with('success', __('Article saved'));
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', __('Article deleted'));
    }

    private function save(Article $article, Request $request): void
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'tag' => ['nullable', 'string', 'max:60'],
            'author' => ['nullable', 'string', 'max:80'],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'body' => ['required', 'string', 'max:60000'],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'tr' => ['nullable', 'array'],
            'meta_title' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:300'],
        ]);

        $article->fill([
            'title' => $data['title'], 'tag' => $data['tag'] ?? null, 'author' => $data['author'] ?? null, 'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'], 'meta_title' => $data['meta_title'] ?? null, 'meta_description' => $data['meta_description'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'published_at' => filled($data['published_at'] ?? null) ? $data['published_at'] : ($article->published_at ?? now()),
        ]);
        $article->setTranslationsFrom($data['tr'] ?? null, ['title', 'excerpt', 'body', 'tag']);
        $article->save();

        if ($request->boolean('remove_cover')) {
            $article->clearMediaCollection('cover');
        }
        if ($request->hasFile('cover')) {
            $article->addMediaFromRequest('cover')->toMediaCollection('cover');
        }
    }
}
