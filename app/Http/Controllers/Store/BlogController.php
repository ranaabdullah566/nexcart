<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $tag = trim((string) $request->query('tag', ''));
        $q = trim((string) $request->query('q', ''));
        $like = '%'.addcslashes($q, '%_\\').'%';

        $articles = Article::live()->with('media')
            ->when($tag !== '', fn ($w) => $w->where('tag', $tag))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like)))
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(9)->withQueryString();

        return view('store.blog', [
            'articles' => $articles, 'tag' => $tag, 'q' => $q,
            'tags' => Article::live()->whereNotNull('tag')->where('tag', '!=', '')->selectRaw('tag, count(*) n')->groupBy('tag')->orderByDesc('n')->pluck('n', 'tag'),
        ]);
    }

    public function show(string $slug): View
    {
        $article = Article::live()->with('media')->where('slug', $slug)->firstOrFail();
        $more = Article::live()->with('media')->whereKeyNot($article->id)
            ->orderByRaw('case when tag = ? then 0 else 1 end', [(string) $article->tag])->orderByDesc('published_at')->limit(3)->get();

        return view('store.article', ['article' => $article, 'more' => $more]);
    }
}
