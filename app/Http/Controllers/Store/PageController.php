<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        return view('store.page', ['cms' => Page::where('slug', $slug)->where('is_published', true)->firstOrFail()]);
    }
}
