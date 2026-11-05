<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Shipping zones (by city) and the rates offered in each, including store pickup. */
class ShippingController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('sales_settings.manage');

        return view('admin.shipping.index', ['zones' => ShippingZone::with('rates')->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        $this->authorizeTo('sales_settings.manage');

        return view('admin.shipping.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('sales_settings.manage');
        $this->save(new ShippingZone, $request);

        return redirect()->route('admin.shipping.index')->with('success', __('Shipping zone saved'));
    }

    public function edit(ShippingZone $shipping): View
    {
        $this->authorizeTo('sales_settings.manage');

        return view('admin.shipping.edit', ['zone' => $shipping->load('rates')]);
    }

    public function update(Request $request, ShippingZone $shipping): RedirectResponse
    {
        $this->authorizeTo('sales_settings.manage');
        $this->save($shipping, $request);

        return redirect()->route('admin.shipping.index')->with('success', __('Shipping zone saved'));
    }

    public function destroy(ShippingZone $shipping): RedirectResponse
    {
        $this->authorizeTo('sales_settings.manage');
        $shipping->delete();

        return redirect()->route('admin.shipping.index')->with('success', __('Shipping zone deleted'));
    }

    private function save(ShippingZone $zone, Request $request): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cities' => ['nullable', 'string', 'max:2000'],
            'rates' => ['required', 'array', 'min:1', 'max:20'],
            'rates.*.id' => ['nullable', 'integer'],
            'rates.*.name' => ['required', 'string', 'max:120'],
            'rates.*.price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rates.*.free_over' => ['nullable', 'numeric', 'min:0'],
            'rates.*.min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'rates.*.eta' => ['nullable', 'string', 'max:60'],
        ]);

        $cities = collect(preg_split('/[\r\n,]+/', (string) ($data['cities'] ?? '')))->map(fn ($c) => trim($c))->filter()->unique(fn ($c) => mb_strtolower($c))->values()->all();

        DB::transaction(function () use ($zone, $data, $request, $cities) {
            $zone->fill(['name' => $data['name'], 'cities' => $cities ?: null, 'is_active' => $request->boolean('is_active')])->save();

            $keep = [];
            foreach (array_values($data['rates']) as $i => $row) {
                $rate = ($row['id'] ?? null) ? $zone->rates()->find($row['id']) : null;
                $rate ??= $zone->rates()->make();
                $pickup = (bool) data_get($request->input('rates'), "$i.is_pickup", false);
                $rate->fill([
                    'name' => $row['name'], 'price' => $pickup ? 0 : ($row['price'] ?? 0), 'free_over' => $pickup ? null : ($row['free_over'] ?? null),
                    'min_subtotal' => $row['min_subtotal'] ?? null, 'eta' => $row['eta'] ?? null, 'is_pickup' => $pickup, 'is_active' => true, 'sort_order' => $i,
                ])->save();
                $keep[] = $rate->id;
            }
            $zone->rates()->whereNotIn('id', $keep)->delete();
        });
    }
}
