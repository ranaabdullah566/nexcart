<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Product;
use App\Models\StockMovement;
use App\Rules\UniqueActive;
use App\Support\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('products.view');

        $products = Product::with(['category', 'variants', 'media'])->orderByDesc('id')->get();

        return view('admin.products.index', [
            'products' => $products,
            'lowStock' => $products->filter->isLowStock()->count(),
            'activeCount' => $products->where('status', 'active')->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeTo('products.create');

        return view('admin.products.create', $this->formData(new Product(['type' => 'simple', 'status' => 'draft', 'track_stock' => true, 'low_stock_threshold' => 5])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('products.create');

        $product = new Product;
        $this->save($product, $request);

        return redirect()->route('admin.products.edit', $product)->with('success', __('Product saved'));
    }

    public function edit(Product $product): View
    {
        $this->authorizeTo('products.update');

        return view('admin.products.edit', $this->formData($product->load(['variants', 'modifierGroups', 'media'])));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeTo('products.update');
        $this->save($product, $request);

        return redirect()->route('admin.products.edit', $product)->with('success', __('Product saved'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorizeTo('products.delete');
        $product->delete();   // soft delete: past orders keep their product

        return redirect()->route('admin.products.index')->with('success', __('Product deleted'));
    }

    public function destroyImage(Product $product, int $media): RedirectResponse
    {
        $this->authorizeTo('products.update');
        $product->media()->whereKey($media)->firstOrFail()->delete();

        return back()->with('success', __('Image removed'));
    }

    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::with('parent.parent')->orderBy('name')->get()->sortBy(fn ($c) => $c->path)->values(),
            'brands' => Brand::orderBy('name')->get(),
            'attributes' => CatalogAttribute::forBusiness()->orderBy('sort_order')->orderBy('label')->get(),
            'modifierGroups' => BusinessProfile::hasModule('modifiers') ? ModifierGroup::where('is_active', true)->orderBy('name')->get() : collect(),
            'showRx' => BusinessProfile::hasModule('prescriptions'),
            'terms' => (array) setting('business.terms', []),
        ];
    }

    private function save(Product $product, Request $request): void
    {
        $creating = ! $product->exists;
        $attributes = CatalogAttribute::forBusiness()->get();

        $rules = [
            'name' => ['required', 'string', 'max:190'],
            'sku' => ['nullable', 'string', 'max:64', new UniqueActive('products', 'sku', $product->id)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'type' => ['required', Rule::in(['simple', 'variable'])],
            'status' => ['required', Rule::in(Product::STATUSES)],
            'short_description' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required_if:type,simple', 'nullable', 'numeric', 'min:0', 'max:99999999'],
            'compare_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'stock_qty' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'tr' => ['nullable', 'array'],
            'modifier_groups' => ['nullable', 'array'],
            'modifier_groups.*' => ['exists:modifier_groups,id'],
            'variants' => ['required_if:type,variable', 'nullable', 'array', 'max:100'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required', 'string', 'max:120'],
            'variants.*.sku' => ['nullable', 'string', 'max:64', 'distinct'],
            'variants.*.price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'variants.*.compare_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'variants.*.stock_qty' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
        foreach ($attributes as $a) {
            $rules["specs.{$a->key}"] = $this->specRules($a);
        }

        $data = $request->validate($rules);

        if ($data['type'] === 'variable' && $creating === false && $product->type === 'simple' && $product->stock_qty > 0) {
            throw ValidationException::withMessages(['type' => __('Remove existing stock first (adjust it to zero) before switching to variants.')]);
        }

        DB::transaction(function () use ($product, $request, $data, $creating, $attributes) {
            $specs = [];
            foreach ($attributes as $a) {
                $value = $data['specs'][$a->key] ?? null;
                if ($a->type === 'boolean') {
                    $value = $request->boolean("specs.{$a->key}");
                }
                if ($value !== null && $value !== '') {
                    $specs[$a->key] = $a->type === 'number' ? $value + 0 : $value;
                }
            }

            $variable = $data['type'] === 'variable';
            $product->fill([
                'category_id' => $data['category_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'name' => $data['name'],
                'sku' => filled($data['sku'] ?? null) ? $data['sku'] : null,
                'type' => $data['type'],
                'status' => $data['status'],
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'price' => $variable ? collect($data['variants'])->min('price') : $data['price'],
                'compare_price' => $variable ? null : ($data['compare_price'] ?? null),
                'cost' => $data['cost'] ?? null,
                'track_stock' => $request->boolean('track_stock'),
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                'requires_prescription' => BusinessProfile::hasModule('prescriptions') && $request->boolean('requires_prescription'),
                'is_featured' => $request->boolean('is_featured'),
                'specs' => $specs ?: null,
            ]);
            $product->setTranslationsFrom($data['tr'] ?? null, ['name', 'short_description', 'description']);

            // Stock is never edited here after creation — use Inventory so every change is logged.
            $opening = ($creating && ! $variable && $product->track_stock) ? (int) ($data['stock_qty'] ?? 0) : 0;
            if ($creating) {
                $product->stock_qty = $opening;
            }
            $product->save();
            if ($opening > 0) {
                $this->openingMovement($product, null, $opening);
            }

            if ($variable) {
                $this->syncVariants($product, $data['variants']);
            } else {
                $product->variants()->delete();
            }

            $product->modifierGroups()->sync(BusinessProfile::hasModule('modifiers') ? ($data['modifier_groups'] ?? []) : []);

            foreach ($request->file('images', []) as $file) {
                $product->addMedia($file)->toMediaCollection('images');
            }
        });
    }

    private function syncVariants(Product $product, array $rows): void
    {
        $keep = [];
        foreach (array_values($rows) as $i => $row) {
            $variant = ($row['id'] ?? null) ? $product->variants()->find($row['id']) : null;
            $isNew = $variant === null;
            $variant ??= $product->variants()->make();

            $variant->fill([
                'name' => $row['name'], 'sku' => filled($row['sku'] ?? null) ? $row['sku'] : null,
                'price' => $row['price'], 'compare_price' => $row['compare_price'] ?? null,
                'is_active' => (bool) ($row['is_active'] ?? true), 'sort_order' => $i,
            ]);
            if ($isNew) {
                $variant->stock_qty = $product->track_stock ? (int) ($row['stock_qty'] ?? 0) : 0;
            }
            $variant->save();
            if ($isNew && $variant->stock_qty > 0) {
                $this->openingMovement($product, $variant, $variant->stock_qty);
            }
            $keep[] = $variant->id;
        }
        $product->variants()->whereNotIn('id', $keep)->delete();
    }

    private function openingMovement(Product $product, $variant, int $qty): void
    {
        StockMovement::create([
            'product_id' => $product->id, 'variant_id' => $variant?->id, 'user_id' => auth()->id(),
            'change' => $qty, 'balance' => $qty, 'reason' => 'received', 'note' => __('Opening stock'),
        ]);
    }

    private function specRules(CatalogAttribute $a): array
    {
        $required = $a->is_required && $a->type !== 'boolean' ? 'required' : 'nullable';

        return match ($a->type) {
            'number' => [$required, 'numeric'],
            'select' => [$required, Rule::in($a->options ?? [])],
            'boolean' => ['nullable'],
            default => [$required, 'string', 'max:255'],
        };
    }
}
