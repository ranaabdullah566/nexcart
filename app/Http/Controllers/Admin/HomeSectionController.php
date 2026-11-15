<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeSection;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Home page builder: add, edit, hide and reorder the sections shoppers see on the front page. */
class HomeSectionController extends Controller
{
    /** Text fields (per type) that can be translated into other languages. */
    private const TRANSLATABLE = [
        'hero' => ['eyebrow', 'title', 'subtitle', 'button_label', 'button2_label'],
        'banner' => ['eyebrow', 'title', 'subtitle', 'button_label'],
        'categories' => ['title'],
        'products' => ['title'],
        'text' => ['title', 'body'],
        'features' => [],
        'cta' => ['eyebrow', 'title', 'subtitle', 'button_label', 'button2_label'],
        'steps' => ['title', 'subtitle'],
        'testimonials' => ['title', 'subtitle'],
        'reviews' => ['title', 'subtitle'],
        'blog' => ['title', 'subtitle'],
        'faq' => ['title', 'subtitle'],
    ];

    /** Sections made of a list of items: [max items, fields per item]. */
    public const ITEMS = [
        'features' => [4, ['icon', 'title', 'text']], 'steps' => [6, ['icon', 'title', 'text']],
        'testimonials' => [6, ['title', 'role', 'text']], 'faq' => [10, ['title', 'text']],
    ];

    public function index(): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.home.index', ['sections' => HomeSection::orderBy('sort_order')->orderBy('id')->get(), 'types' => HomeSection::TYPES]);
    }

    public function create(Request $request): View
    {
        $this->authorizeTo('storefront.manage');
        $type = $request->query('type');
        abort_unless(in_array($type, HomeSection::TYPES, true), 404);

        return view('admin.home.create', $this->formData(new HomeSection(['type' => $type, 'settings' => [], 'is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $type = $request->input('type');
        abort_unless(in_array($type, HomeSection::TYPES, true), 422);

        $section = new HomeSection(['type' => $type, 'sort_order' => (int) HomeSection::max('sort_order') + 1]);
        $this->save($section, $request);

        return redirect()->route('admin.home-sections.index')->with('success', __('Section saved'));
    }

    public function edit(HomeSection $homeSection): View
    {
        $this->authorizeTo('storefront.manage');

        return view('admin.home.edit', $this->formData($homeSection));
    }

    public function update(Request $request, HomeSection $homeSection): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $this->save($homeSection, $request);

        return redirect()->route('admin.home-sections.index')->with('success', __('Section saved'));
    }

    public function destroy(HomeSection $homeSection): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $homeSection->delete();

        return redirect()->route('admin.home-sections.index')->with('success', __('Section deleted'));
    }

    public function toggle(HomeSection $homeSection): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $homeSection->update(['is_active' => ! $homeSection->is_active]);

        return back();
    }

    public function move(Request $request, HomeSection $homeSection): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $dir = $request->validate(['dir' => ['required', Rule::in(['up', 'down'])]])['dir'];

        $ordered = HomeSection::orderBy('sort_order')->orderBy('id')->get()->values();
        $i = $ordered->search(fn ($s) => $s->id === $homeSection->id);
        $j = $dir === 'up' ? $i - 1 : $i + 1;

        if (isset($ordered[$j])) {
            $swap = $ordered->all();
            [$swap[$i], $swap[$j]] = [$swap[$j], $swap[$i]];
            foreach ($swap as $pos => $s) {   // renumber so equal sort_orders can never trap a section
                $s->update(['sort_order' => $pos]);
            }
        }

        return back();
    }

    private function itemRules(string $type): array
    {
        [$max, $fields] = self::ITEMS[$type];
        $rules = ['items' => ['required', 'array', 'max:'.$max], 'title' => ['nullable', 'string', 'max:120'], 'subtitle' => ['nullable', 'string', 'max:300']];
        foreach ($fields as $f) {
            $rules["items.*.$f"] = $f === 'icon' ? ['nullable', 'regex:/^[a-z0-9-]{2,40}$/'] : ['nullable', 'string', 'max:'.($f === 'text' ? ($type === 'faq' ? 600 : 240) : 80)];
        }

        return $rules;
    }

    private function formData(HomeSection $section): array
    {
        return ['section' => $section, 'categories' => Category::where('is_active', true)->orderBy('name')->get(), 'translatable' => self::TRANSLATABLE[$section->type]];
    }

    private function save(HomeSection $section, Request $request): void
    {
        $type = $section->type;
        $url = ['nullable', 'string', 'max:300', 'regex:/^(\/|https?:\/\/)/'];   // no javascript: links

        $rules = ['is_active' => ['nullable'], 'tr' => ['nullable', 'array'], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']];
        $rules += match ($type) {
            'hero' => ['eyebrow' => ['nullable', 'string', 'max:80'], 'title' => ['required', 'string', 'max:120'], 'subtitle' => ['nullable', 'string', 'max:300'],
                'button_label' => ['nullable', 'string', 'max:40'], 'button_url' => $url, 'button2_label' => ['nullable', 'string', 'max:40'], 'button2_url' => $url],
            'banner' => ['eyebrow' => ['nullable', 'string', 'max:80'], 'title' => ['required', 'string', 'max:120'], 'subtitle' => ['nullable', 'string', 'max:300'],
                'button_label' => ['nullable', 'string', 'max:40'], 'button_url' => $url],
            'categories' => ['title' => ['nullable', 'string', 'max:120'], 'limit' => ['nullable', 'integer', 'min:1', 'max:24'], 'category_ids' => ['nullable', 'array'], 'category_ids.*' => ['exists:categories,id']],
            'products' => ['title' => ['nullable', 'string', 'max:120'], 'limit' => ['nullable', 'integer', 'min:1', 'max:24'], 'source' => ['required', Rule::in(['new', 'featured', 'category'])],
                'category_id' => ['nullable', 'required_if:source,category', 'exists:categories,id']],
            'text' => ['title' => ['nullable', 'string', 'max:120'], 'body' => ['required', 'string', 'max:5000']],
            'features', 'steps', 'testimonials', 'faq' => $this->itemRules($type),
            'blog' => ['title' => ['nullable', 'string', 'max:120'], 'subtitle' => ['nullable', 'string', 'max:300'], 'limit' => ['nullable', 'integer', 'min:1', 'max:6']],
            'reviews' => ['title' => ['nullable', 'string', 'max:120'], 'subtitle' => ['nullable', 'string', 'max:300'], 'limit' => ['nullable', 'integer', 'min:3', 'max:24'], 'min_rating' => ['required', Rule::in([1, 2, 3, 4, 5])], 'show_summary' => ['nullable', 'boolean']],
            'cta' => ['eyebrow' => ['nullable', 'string', 'max:80'], 'title' => ['required', 'string', 'max:120'], 'subtitle' => ['nullable', 'string', 'max:300'],
                'button_label' => ['nullable', 'string', 'max:40'], 'button_url' => $url, 'button2_label' => ['nullable', 'string', 'max:40'], 'button2_url' => $url],
        };
        $data = $request->validate($rules);

        $settings = collect($data)->except(['is_active', 'tr', 'image'])->all();
        if (isset(self::ITEMS[$type])) {
            $settings['items'] = collect($data['items'])->filter(fn ($i) => filled($i['title'] ?? null))->values()->all();
        }
        if ($type === 'reviews') {
            $settings['show_summary'] = $request->boolean('show_summary');
        }
        if ($type === 'categories') {
            $settings['category_ids'] = array_values(array_filter($data['category_ids'] ?? []));
        }

        $section->fill([
            'title' => $data['title'] ?? ($type === 'features' ? __('Why shop with us') : ucfirst($type)),
            'settings' => $settings,
            'is_active' => $request->boolean('is_active'),
        ]);
        $section->translations = $this->translations($type, $data['tr'] ?? [], count($settings['items'] ?? []));
        $section->save();

        if ($request->boolean('remove_image')) {
            $section->clearMediaCollection('image');
        }
        if ($request->hasFile('image') && in_array($type, ['hero', 'banner', 'cta'], true)) {
            $section->addMediaFromRequest('image')->toMediaCollection('image');
        }
    }

    /** Keep only known locales and fields; features also carry per-item title/text. */
    private function translations(string $type, array $input, int $items): ?array
    {
        $out = [];
        foreach (array_keys(Locales::all()) as $locale) {
            if ($locale === 'en') {
                continue;
            }
            foreach (self::TRANSLATABLE[$type] as $field) {
                $text = trim((string) ($input[$locale][$field] ?? ''));
                if ($text !== '') {
                    $out[$locale][$field] = mb_substr($text, 0, 5000);
                }
            }
            if (isset(self::ITEMS[$type])) {
                for ($i = 0; $i < $items; $i++) {
                    foreach (array_diff(self::ITEMS[$type][1], ['icon']) as $f) {
                        $text = trim((string) ($input[$locale]['items'][$i][$f] ?? ''));
                        if ($text !== '') {
                            $out[$locale]['items'][$i][$f] = mb_substr($text, 0, 600);
                        }
                    }
                }
            }
        }

        return $out ?: null;
    }
}
