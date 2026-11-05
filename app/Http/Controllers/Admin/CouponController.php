<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Rules\UniqueActive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('sales_settings.manage');

        return view('admin.coupons.index', ['coupons' => Coupon::latest('id')->get()]);
    }

    public function create(): View
    {
        $this->authorizeTo('sales_settings.manage');

        return view('admin.coupons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('sales_settings.manage');
        $this->save(new Coupon, $request);

        return redirect()->route('admin.coupons.index')->with('success', __('Coupon saved'));
    }

    public function edit(Coupon $coupon): View
    {
        $this->authorizeTo('sales_settings.manage');

        return view('admin.coupons.edit', ['coupon' => $coupon]);
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $this->authorizeTo('sales_settings.manage');
        $this->save($coupon, $request);

        return redirect()->route('admin.coupons.index')->with('success', __('Coupon saved'));
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorizeTo('sales_settings.manage');
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', __('Coupon deleted'));
    }

    private function save(Coupon $coupon, Request $request): void
    {
        $request->merge(['code' => Coupon::normalize((string) $request->input('code'))]);

        $data = $request->validate([
            'code' => ['required', 'regex:/^[A-Z0-9_-]{3,40}$/', new UniqueActive('coupons', 'code', $coupon->id)],
            'type' => ['required', Rule::in(Coupon::TYPES)],
            'value' => [Rule::requiredIf(fn () => $request->input('type') !== 'free_shipping'), 'nullable', 'numeric', 'min:0', 'max:99999999',
                Rule::when($request->input('type') === 'percent', ['max:100'])],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:10000000'],
        ]);

        $coupon->fill([
            'code' => $data['code'], 'type' => $data['type'], 'value' => $data['type'] === 'free_shipping' ? 0 : $data['value'],
            'min_subtotal' => $data['min_subtotal'] ?? null, 'max_discount' => $data['type'] === 'percent' ? ($data['max_discount'] ?? null) : null,
            'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null, 'usage_limit' => $data['usage_limit'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ])->save();
    }
}
