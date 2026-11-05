<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV round-trip for simple products (variants are managed in the product form). */
class ProductCsvController extends Controller
{
    private const COLUMNS = ['sku', 'name', 'category', 'brand', 'price', 'compare_price', 'cost', 'stock_qty', 'status', 'description'];

    public function export(): StreamedResponse
    {
        $this->authorizeTo('products.view');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::COLUMNS);
            Product::with(['category', 'brand'])->where('type', 'simple')->orderBy('id')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $p) {
                    fputcsv($out, array_map([$this, 'safe'], [
                        $p->sku, $p->name, $p->category?->name, $p->brand?->name, $p->price, $p->compare_price, $p->cost, $p->stock_qty, $p->status, $p->description,
                    ]));
                }
            });
            fclose($out);
        }, 'products-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizeTo('products.create');
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), (array) fgetcsv($handle));

        if (! in_array('name', $header, true)) {
            return back()->with('error', __('The file needs at least a "name" column.'));
        }

        $created = $updated = $skipped = 0;
        DB::transaction(function () use ($handle, $header, &$created, &$updated, &$skipped) {
            while (($line = fgetcsv($handle)) !== false) {
                if (count($line) < count($header)) {
                    $line = array_pad($line, count($header), null);
                }
                $row = array_combine($header, array_slice($line, 0, count($header)));
                $name = trim((string) ($row['name'] ?? ''));
                $price = $row['price'] ?? '0';

                if ($name === '' || ! is_numeric($price) || $price < 0) {
                    $skipped++;

                    continue;
                }

                $sku = trim((string) ($row['sku'] ?? '')) ?: null;
                $product = $sku ? Product::withTrashed()->where('sku', $sku)->first() : null;
                if ($product && $product->type !== 'simple') {
                    $skipped++;

                    continue;
                }
                $isNew = $product === null;
                $product ??= new Product(['type' => 'simple', 'track_stock' => true]);

                $status = in_array($row['status'] ?? '', Product::STATUSES, true) ? $row['status'] : ($isNew ? 'draft' : $product->status);
                $product->fill([
                    'sku' => $sku, 'name' => $name, 'price' => $price, 'status' => $status,
                    'compare_price' => is_numeric($row['compare_price'] ?? null) ? $row['compare_price'] : null,
                    'cost' => is_numeric($row['cost'] ?? null) ? $row['cost'] : null,
                    'description' => trim((string) ($row['description'] ?? '')) ?: null,
                    'category_id' => filled($row['category'] ?? null) ? Category::firstOrCreate(['name' => trim($row['category'])])->id : null,
                    'brand_id' => filled($row['brand'] ?? null) ? Brand::firstOrCreate(['name' => trim($row['brand'])])->id : null,
                ]);
                if ($isNew) {
                    $product->stock_qty = max(0, (int) ($row['stock_qty'] ?? 0));   // later changes go through Inventory
                }
                if ($product->trashed()) {
                    $product->restore();
                }
                $product->save();
                $isNew ? $created++ : $updated++;
            }
        });
        fclose($handle);

        return back()->with('success', __('Import finished: :c added, :u updated, :s skipped', ['c' => $created, 'u' => $updated, 's' => $skipped]));
    }

    /** Stop spreadsheet formula injection in exported cells. */
    private function safe(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
