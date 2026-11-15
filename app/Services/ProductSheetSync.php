<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSync;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Common\Entity\Row;

/**
 * Weekly product sheet sync (the Foodpanda product export, or any sheet with the same columns).
 *
 *   sku | name | price | active | maximumSalesQuantity | isWeighted | baseWeight | baseWeightUnit | pricePerBaseUnit | barcode 1…N | category 1…N
 *
 * The SKU is the key: a new SKU creates a product, a known SKU updates it, an identical row is skipped. Everything in the sheet is kept
 * (columns we have no field for are stored in products.source_data). Stock, pictures, description and other fields edited in the admin are never touched.
 */
class ProductSheetSync
{
    public const COLUMNS = ['sku', 'name', 'price', 'active', 'maximumSalesQuantity', 'isWeighted', 'baseWeight', 'baseWeightUnit', 'pricePerBaseUnit', 'barcode 1', 'barcode 2', 'barcode 3', 'barcode 4', 'barcode 5', 'category 1'];

    private const KNOWN = ['sku', 'name', 'price', 'active', 'maximumsalesquantity', 'isweighted', 'baseweight', 'baseweightunit', 'priceperbaseunit'];

    /**
     * @param  array{update_existing?: bool, use_active?: bool, deactivate_missing?: bool}  $options
     */
    public function run(string $path, string $extension, string $filename, array $options = [], $user = null): ProductSync
    {
        $opt = $options + ['update_existing' => true, 'use_active' => true, 'deactivate_missing' => false];
        $started = microtime(true);
        @set_time_limit(900);

        $rows = $this->read($path, $extension);
        $res = ['rows' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'deactivated' => 0, 'categories_created' => 0];
        $errors = [];
        $seen = [];
        $cats = Category::withTrashed()->get()->mapWithKeys(fn ($c) => [mb_strtolower(trim($c->name)) => $c])->all();
        $bad = function (int $line, string $why, ?string $sku = null) use (&$errors, &$res) {
            $res['skipped']++;
            if (count($errors) < 300) {
                $errors[] = ['row' => $line, 'sku' => $sku, 'error' => $why];
            }
        };

        $parsed = [];
        foreach ($rows as $line => $raw) {
            $res['rows']++;
            $r = $this->normalise($raw);
            if ($r['error']) {
                $bad($line, $r['error'], $r['sku']);

                continue;
            }
            if (isset($seen[$r['sku']])) {
                $bad($line, __('Duplicate SKU in the sheet (the first row was used)'), $r['sku']);

                continue;
            }
            $seen[$r['sku']] = true;
            $parsed[$line] = $r;
        }

        \Spatie\Activitylog\Facades\Activity::withoutLogs(function () use ($parsed, $opt, &$res, &$cats, &$seen) {
            foreach (array_chunk($parsed, 250, true) as $chunk) {
                $existing = Product::withTrashed()->whereIn('sku', array_column($chunk, 'sku'))->get()->keyBy('sku');
                DB::transaction(function () use ($chunk, $existing, $opt, &$res, &$cats, &$seen) {
                    foreach ($chunk as $r) {
                        $product = $existing->get($r['sku']);
                        $hash = sha1(json_encode([$r['fingerprint'], (bool) $opt['use_active']]));

                        if ($product && ! $opt['update_existing']) {
                            $res['unchanged']++;
                            $seen[$r['sku']] = $product->id;

                            continue;
                        }
                        if ($product && $product->source_hash === $hash && ! $product->trashed()) {
                            $res['unchanged']++;
                            $seen[$r['sku']] = $product->id;

                            continue;
                        }

                        $categoryId = null;
                        if ($r['categories']) {
                            $key = mb_strtolower($r['categories'][0]);
                            if (! isset($cats[$key])) {
                                $cats[$key] = Category::create(['name' => $r['categories'][0], 'is_active' => true]);
                                $res['categories_created']++;
                            }
                            $categoryId = $cats[$key]->id;
                        }

                        $fields = [
                            'name' => $r['name'], 'price' => $r['price'],
                            'max_per_order' => $r['max_per_order'], 'is_weighted' => $r['is_weighted'], 'base_weight' => $r['base_weight'],
                            'base_weight_unit' => $r['base_weight_unit'], 'price_per_base_unit' => $r['price_per_base_unit'],
                            'barcodes' => $r['barcodes'] ?: null,
                            'source' => 'sheet', 'source_hash' => $hash, 'synced_at' => now(),
                            'source_data' => ['categories' => $r['categories'], 'extra' => $r['extra']] ?: null,
                        ];
                        if ($categoryId) {
                            $fields['category_id'] = $categoryId;
                        }
                        if ($opt['use_active']) {
                            $fields['status'] = $r['active'] ? 'active' : 'draft';
                        }

                        if ($product) {
                            if ($product->trashed()) {
                                $product->restore();
                            }
                            $product->forceFill($fields)->save();
                            $res['updated']++;
                        } else {
                            $product = new Product;
                            $product->forceFill($fields + [
                                'sku' => $r['sku'], 'type' => 'simple', 'status' => $fields['status'] ?? 'active',
                                'track_stock' => false, 'stock_qty' => 0, 'low_stock_threshold' => 5,   // the sheet has no stock: sells until you start tracking it
                            ])->save();
                            $res['created']++;
                        }
                        $seen[$r['sku']] = $product->id;
                    }
                });
            }
        });

        if ($opt['deactivate_missing']) {
            $keep = array_values(array_filter($seen, 'is_int'));
            $keepSet = array_flip($keep);
            $gone = Product::where('source', 'sheet')->where('status', 'active')->pluck('id')->reject(fn ($id) => isset($keepSet[$id]))->values();
            foreach ($gone->chunk(500) as $ids) {
                $res['deactivated'] += Product::whereIn('id', $ids)->update(['status' => 'draft']);
            }
        }

        $run = ProductSync::create($res + [
            'user_id' => ($user ?? auth()->user())?->id, 'filename' => mb_substr($filename, 0, 190), 'options' => $opt, 'errors' => $errors ?: null,
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
        ]);
        Audit::log('products.sync', ['file' => $filename] + $res, null, $user);

        return $run;
    }

    /** @return \Generator<int, array<string, mixed>> sheet rows keyed by their line number, as header => value */
    private function read(string $path, string $extension): array
    {
        $reader = strtolower($extension) === 'csv' ? new CsvReader : new XlsxReader;
        $reader->open($path);
        $header = null;
        $out = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $i => $row) {
                $cells = $row->toArray();
                if ($header === null) {
                    $header = array_map(fn ($h) => trim((string) $h), $cells);

                    continue;
                }
                if (! array_filter($cells, fn ($v) => $v !== null && $v !== '')) {
                    continue;
                }
                $assoc = [];
                foreach ($header as $c => $h) {
                    if ($h !== '') {
                        $assoc[$h] = $cells[$c] ?? null;
                    }
                }
                $out[$i] = $assoc;
            }
            break;   // the first sheet only
        }
        $reader->close();

        return $out;
    }

    private function truthy(mixed $v): bool
    {
        if (is_bool($v)) {
            return $v;
        }

        return in_array(mb_strtolower(trim((string) $v)), ['1', 'true', 'yes', 'y', 'active', 'on'], true);
    }

    private function num(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $s = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $v));

        return is_numeric($s) ? (float) $s : null;
    }

    /** @return array<string, mixed> */
    private function normalise(array $raw): array
    {
        $by = [];
        $extra = [];
        $cats = [];
        $codes = [];
        foreach ($raw as $h => $v) {
            $k = mb_strtolower(str_replace([' ', '_', '-'], '', (string) $h));
            if (preg_match('/^barcode(\d*)$/', $k)) {
                $v = trim((string) $v);
                if ($v !== '') {
                    $codes[] = $v;
                }
            } elseif (preg_match('/^category(\d*)$/', $k)) {
                $v = trim((string) $v);
                if ($v !== '') {
                    $cats[] = $v;
                }
            } elseif (in_array($k, self::KNOWN, true)) {
                $by[$k] = $v;
            } elseif ($v !== null && $v !== '') {
                $extra[(string) $h] = is_scalar($v) ? $v : json_encode($v);
            }
        }

        $sku = trim((string) ($by['sku'] ?? ''));
        $name = trim((string) ($by['name'] ?? ''));
        $price = $this->num($by['price'] ?? null);
        $out = ['sku' => $sku !== '' ? $sku : null, 'error' => null];
        if ($sku === '' || mb_strlen($sku) > 64) {
            $out['error'] = __('Missing or too long SKU');
        } elseif ($name === '') {
            $out['error'] = __('Missing name');
        } elseif ($price === null || $price < 0 || $price > 99999999) {
            $out['error'] = __('Price is not a valid number');
        }
        if ($out['error']) {
            return $out;
        }

        $max = $this->num($by['maximumsalesquantity'] ?? null);
        $weighted = $this->truthy($by['isweighted'] ?? false);
        $base = $this->num($by['baseweight'] ?? null);
        $ppu = $this->num($by['priceperbaseunit'] ?? null);
        $unit = trim((string) ($by['baseweightunit'] ?? ''));
        $codes = array_values(array_unique($codes));

        $out += [
            'name' => mb_substr($name, 0, 255), 'price' => round($price, 2), 'active' => $this->truthy($by['active'] ?? false),
            'max_per_order' => $max && $max > 0 ? (int) min($max, 65535) : null, 'is_weighted' => $weighted,
            'base_weight' => $base, 'base_weight_unit' => $unit !== '' ? mb_substr($unit, 0, 16) : null, 'price_per_base_unit' => $ppu !== null ? round($ppu, 2) : null,
            'barcodes' => $codes, 'categories' => array_values(array_unique($cats)), 'extra' => $extra,
        ];
        $out['fingerprint'] = array_diff_key($out, ['error' => 1]);

        return $out;
    }

    /** Download the current products in the same column layout, ready to edit and upload again. */
    public function export(string $path): void
    {
        $w = new XlsxWriter;
        $w->openToFile($path);
        $max = 5;
        $cats = 1;
        $q = Product::with('category')->where('type', 'simple')->orderBy('id');
        $header = ['sku', 'name', 'price', 'active', 'maximumSalesQuantity', 'isWeighted', 'baseWeight', 'baseWeightUnit', 'pricePerBaseUnit'];
        foreach (range(1, $max) as $i) {
            $header[] = 'barcode '.$i;
        }
        $header[] = 'category 1';
        $w->addRow(Row::fromValues($header));
        $q->chunk(500, function ($chunk) use ($w, $max) {
            foreach ($chunk as $p) {
                $codes = array_values($p->barcodes ?? []);
                $line = [$p->sku, $p->name, (string) $p->price, $p->status === 'active', $p->max_per_order, (bool) $p->is_weighted, $p->base_weight, $p->base_weight_unit, $p->price_per_base_unit];
                foreach (range(0, $max - 1) as $i) {
                    $line[] = $codes[$i] ?? null;
                }
                $line[] = $p->category?->name;
                $w->addRow(Row::fromValues($line));
            }
        });
        $w->close();
    }
}
