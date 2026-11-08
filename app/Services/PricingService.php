<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\ShippingRate;

/** One place that turns "cart subtotal + coupon + shipping rate" into the numbers a customer pays. */
class PricingService
{
    /**
     * @return array{subtotal: float, discount: float, shipping: float, tax: float, total: float, tax_rate: float, tax_inclusive: bool, coupon_problem: ?string}
     */
    public function quote(float $subtotal, ?Coupon $coupon = null, ?ShippingRate $rate = null): array
    {
        $subtotal = round($subtotal, 2);
        $problem = $coupon?->problem($subtotal);
        $validCoupon = $coupon && ! $problem ? $coupon : null;

        $discount = $validCoupon ? $validCoupon->discountFor($subtotal) : 0.0;
        $shipping = $rate ? $rate->costFor($subtotal) : 0.0;
        if ($validCoupon?->type === 'free_shipping') {
            $shipping = 0.0;
        }

        $taxRate = max(0.0, (float) setting('store.tax_rate', 0));
        $inclusive = (bool) setting('store.tax_inclusive', false);
        $taxable = max(0.0, $subtotal - $discount);

        $tax = $inclusive ? $taxable - $taxable / (1 + $taxRate / 100) : $taxable * $taxRate / 100;
        $tax = round($tax, 2);
        $total = round($taxable + $shipping + ($inclusive ? 0 : $tax), 2);

        return [
            'subtotal' => $subtotal, 'discount' => $discount, 'shipping' => round($shipping, 2), 'tax' => $tax, 'total' => $total,
            'tax_rate' => $taxRate, 'tax_inclusive' => $inclusive, 'coupon_problem' => $problem,
        ];
    }
}
