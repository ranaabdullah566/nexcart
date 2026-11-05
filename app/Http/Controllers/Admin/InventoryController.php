<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\StockService;
use App\Support\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('inventory.view');

        $products = Product::with('variants')->where('track_stock', true)->orderBy('name')->get();

        // one row per sellable unit: a simple product, or each variant of a variable product
        $rows = $products->flatMap(function (Product $p) {
            if ($p->isVariable()) {
                return $p->variants->map(fn ($v) => ['product' => $p, 'variant' => $v, 'qty' => $v->stock_qty, 'sku' => $v->sku ?: $p->sku]);
            }

            return [['product' => $p, 'variant' => null, 'qty' => $p->stock_qty, 'sku' => $p->sku]];
        })->values();

        $batches = BusinessProfile::hasModule('batch_expiry')
            ? ProductBatch::with('product')->where('qty', '>', 0)->orderByRaw('expires_at is null')->orderBy('expires_at')->limit(200)->get()
            : collect();

        return view('admin.inventory.index', [
            'rows' => $rows,
            'lowCount' => $rows->filter(fn ($r) => $r['qty'] <= $r['product']->low_stock_threshold)->count(),
            'outCount' => $rows->where('qty', 0)->count(),
            'batches' => $batches,
            'expiringSoon' => $batches->filter(fn ($b) => $b->expires_at && $b->expires_at->lte(now()->addDays(60)))->count(),
            'movements' => StockMovement::with(['product', 'variant', 'user'])->latest()->limit(100)->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeTo('inventory.adjust');

        $products = Product::with('variants')->where('track_stock', true)->orderBy('name')->get();

        return view('admin.inventory.adjust', [
            'products' => $products,
            'selected' => $request->query('item'),   // "p:12" or "v:5"
            'useBatches' => BusinessProfile::hasModule('batch_expiry'),
            'reasons' => StockMovement::REASONS,
        ]);
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        $this->authorizeTo('inventory.adjust');

        $data = $request->validate([
            'item' => ['required', 'regex:/^[pv]:\d+$/'],
            'direction' => ['required', Rule::in(['in', 'out'])],
            'qty' => ['required', 'integer', 'min:1', 'max:9999999'],
            'reason' => ['required', Rule::in(StockMovement::REASONS)],
            'note' => ['nullable', 'string', 'max:190'],
            'batch_no' => ['nullable', 'string', 'max:64'],
            'expires_at' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        [$kind, $id] = explode(':', $data['item']);
        $variant = $kind === 'v' ? ProductVariant::findOrFail($id) : null;
        $product = $variant ? $variant->product : Product::findOrFail($id);

        $stock->adjust(
            $product,
            $data['direction'] === 'in' ? $data['qty'] : -$data['qty'],
            $data['reason'],
            $variant,
            ['batch_no' => $data['batch_no'] ?? null, 'expires_at' => $data['expires_at'] ?? null, 'cost' => $data['cost'] ?? null],
            $data['note'] ?? null,
            $request->user()->id,
        );

        return redirect()->route('admin.inventory.index')->with('success', __('Stock updated'));
    }
}
