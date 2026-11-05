<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Extra links in the storefront header and footer. */
class MenuItemController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.menus.index', ['items' => MenuItem::with(['page', 'category'])->orderBy('location')->orderBy('sort_order')->orderBy('id')->get()->groupBy('location')]);
    }

    public function create(Request $request): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.menus.create', $this->formData(['location' => in_array($request->query('location'), MenuItem::LOCATIONS, true) ? $request->query('location') : 'header']));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $this->save(new MenuItem, $request);

        return redirect()->route('admin.menu-items.index')->with('success', __('Link saved'));
    }

    public function edit(MenuItem $menuItem): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.menus.edit', $this->formData([], $menuItem));
    }

    public function update(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $this->save($menuItem, $request);

        return redirect()->route('admin.menu-items.index')->with('success', __('Link saved'));
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $menuItem->delete();

        return redirect()->route('admin.menu-items.index')->with('success', __('Link deleted'));
    }

    public function move(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $dir = $request->validate(['dir' => ['required', Rule::in(['up', 'down'])]])['dir'];

        $ordered = MenuItem::where('location', $menuItem->location)->orderBy('sort_order')->orderBy('id')->get()->values();
        $i = $ordered->search(fn ($m) => $m->id === $menuItem->id);
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if (isset($ordered[$j])) {
            $list = $ordered->all();
            [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
            foreach ($list as $pos => $m) {
                $m->update(['sort_order' => $pos]);
            }
        }

        return back();
    }

    public function toggle(MenuItem $menuItem): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $menuItem->update(['is_active' => ! $menuItem->is_active]);

        return back();
    }

    private function formData(array $defaults, ?MenuItem $item = null): array
    {
        return ['item' => $item ?? new MenuItem($defaults + ['type' => 'url', 'is_active' => true]), 'pages' => Page::where('is_published', true)->orderBy('title')->get(), 'categories' => Category::where('is_active', true)->orderBy('name')->get()];
    }

    private function save(MenuItem $item, Request $request): void
    {
        $data = $request->validate([
            'location' => ['required', Rule::in(MenuItem::LOCATIONS)],
            'label' => ['required', 'string', 'max:60'],
            'type' => ['required', Rule::in(['url', 'page', 'category'])],
            'url' => ['nullable', 'required_if:type,url', 'string', 'max:300', 'regex:/^(\/|https?:\/\/|mailto:|tel:)/'],   // never javascript:
            'page_id' => ['nullable', 'required_if:type,page', 'exists:pages,id'],
            'category_id' => ['nullable', 'required_if:type,category', 'exists:categories,id'],
            'tr' => ['nullable', 'array'],
        ]);

        $item->fill([
            'location' => $data['location'], 'label' => $data['label'], 'type' => $data['type'],
            'url' => $data['type'] === 'url' ? $data['url'] : null,
            'page_id' => $data['type'] === 'page' ? $data['page_id'] : null,
            'category_id' => $data['type'] === 'category' ? $data['category_id'] : null,
            'is_active' => $request->boolean('is_active'),
        ]);
        if (! $item->exists) {
            $item->sort_order = (int) MenuItem::where('location', $data['location'])->max('sort_order') + 1;
        }
        $item->setTranslationsFrom($data['tr'] ?? null, ['label']);
        $item->save();
    }
}
