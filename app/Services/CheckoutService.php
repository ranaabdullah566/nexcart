<?php

namespace App\Services;

use App\Models\AbandonedCheckout;
use App\Models\Coupon;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\Payments\PaymentManager;
use App\Support\BusinessProfile;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private CartService $cart,
        private PricingService $pricing,
        private StockService $stock,
        private OrderService $orders,
        private PaymentManager $payments,
        private SlotService $slots,
        private Loyalty $loyalty,
    ) {}

    /** Shipping rates a customer may pick for a city and subtotal (pickup rates ignore the city). */
    public function ratesFor(?string $city, float $subtotal)
    {
        $zone = ShippingZone::forCity($city);
        $delivery = $zone ? $zone->rates->filter(fn ($r) => ! $r->is_pickup) : collect();

        $pickup = BusinessProfile::hasModule('pickup')
            ? ShippingRate::with('zone')->where('is_pickup', true)->where('is_active', true)->whereHas('zone', fn ($q) => $q->where('is_active', true))->orderBy('sort_order')->get()
            : collect();

        return $delivery->merge($pickup)->filter(fn ($r) => $r->isAvailableFor($subtotal))->values();
    }

    /** Totals for the order summary, without creating anything. */
    public function quote(?string $city, ?int $rateId, ?string $couponCode, ?User $user = null, bool $usePoints = false): array
    {
        $subtotal = $this->cart->subtotal();
        $rates = $this->ratesFor($city, $subtotal);
        $rate = $rateId ? $rates->firstWhere('id', $rateId) : null;
        $coupon = filled($couponCode) ? Coupon::findByCode($couponCode) : null;

        $q = $this->pricing->quote($subtotal, $coupon, $rate);
        $points = ['points' => 0, 'discount' => 0.0];
        if ($user && $usePoints) {
            $points = $this->loyalty->redeemable($user, $subtotal - $q['discount']);
            $q = $this->pricing->quote($subtotal, $coupon, $rate, $points['discount']);
        }

        return $q + [
            'rate' => $rate, 'rates' => $rates, 'coupon' => $coupon,
            'coupon_error' => filled($couponCode) ? ($coupon ? null : __('We could not find that code.')) : null,
            'points_used' => $points['points'],
            'loyalty' => $user && $this->loyalty->enabled() ? ['balance' => $this->loyalty->balance($user), 'min' => $this->loyalty->minRedeem(), 'value' => $this->loyalty->pointValue()] : null,
        ];
    }

    /** Kitchen time (slowest item) plus the rate's travel time; null when the rate has no estimate. */
    private function eta(ShippingRate $rate, $lines): ?Carbon
    {
        if (! $rate->eta_minutes) {
            return null;
        }
        $prep = (int) $lines->map(fn ($l) => (int) ($l['product']->specs['prep_time_min'] ?? 0))->max();

        return now()->addMinutes($prep + $rate->eta_minutes);
    }

    public function needsPrescription(): bool
    {
        return BusinessProfile::hasModule('prescriptions')
            && $this->cart->lines()->contains(fn ($l) => $l['product']->requires_prescription);
    }

    /**
     * @param  array{name:string,email:string,phone:string,address_line?:?string,city?:?string,area?:?string,postal_code?:?string,notes?:?string,shipping_rate_id:int,payment_method:string,coupon?:?string}  $data
     * @param  array<int, UploadedFile>  $prescriptions
     */
    public function place(array $data, ?User $user = null, array $prescriptions = []): Order
    {
        $order = DB::transaction(function () use ($data, $user, $prescriptions) {
            $lines = $this->cart->lines();
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['cart' => __('Your cart is empty.')]);
            }
            foreach ($lines as $l) {
                if ($l['short']) {
                    throw ValidationException::withMessages(['cart' => __('Only :n left of ":name". Please update your cart.', ['n' => $l['available'], 'name' => $l['product']->trans('name')])]);
                }
            }

            $gateway = $this->payments->find($data['payment_method'] ?? '');
            if (! $gateway) {
                throw ValidationException::withMessages(['payment_method' => __('Please choose a payment method.')]);
            }

            $rx = $this->needsPrescription();
            if ($rx && count($prescriptions) === 0) {
                throw ValidationException::withMessages(['prescription' => __('Please upload your prescription.')]);
            }

            $subtotal = $this->cart->subtotal();
            $rate = ShippingRate::with('zone')->where('is_active', true)->find((int) $data['shipping_rate_id']);
            if (! $rate) {
                throw ValidationException::withMessages(['shipping_rate_id' => __('Please choose a delivery option available for your city.')]);
            }
            if (! $rate->is_pickup && blank($data['city'] ?? null)) {
                throw ValidationException::withMessages(['city' => __('Please enter your city.')]);
            }
            // the rate must really be offered for this city and subtotal (pickup rates ignore the city)
            if (! $this->ratesFor($data['city'] ?? null, $subtotal)->contains('id', $rate->id)) {
                throw ValidationException::withMessages(['shipping_rate_id' => __('Please choose a delivery option available for your city.')]);
            }
            if (! $rate->is_pickup && blank($data['address_line'] ?? null)) {
                throw ValidationException::withMessages(['address_line' => __('Please enter your delivery address.')]);
            }

            $slot = null;
            if ($this->slots->enabled()) {
                $slot = $this->slots->check($data['slot_date'] ?? null, isset($data['delivery_slot_id']) ? (int) $data['delivery_slot_id'] : null);
                if (is_string($slot)) {
                    throw ValidationException::withMessages(['delivery_slot_id' => $slot]);
                }
                DeliverySlot::whereKey($slot->id)->lockForUpdate()->first();   // serialise bookings for the same slot
                $slot = $this->slots->check($data['slot_date'], $slot->id);
                if (is_string($slot)) {
                    throw ValidationException::withMessages(['delivery_slot_id' => $slot]);
                }
            }

            $coupon = null;
            if (filled($data['coupon'] ?? null)) {
                $coupon = Coupon::where('code', Coupon::normalize($data['coupon']))->lockForUpdate()->first();
                $problem = $coupon ? $coupon->problem($subtotal) : __('We could not find that code.');
                if ($problem) {
                    throw ValidationException::withMessages(['coupon' => $problem]);
                }
            }

            $points = ['points' => 0, 'discount' => 0.0];
            if ($user && ! empty($data['use_points'])) {
                $points = $this->loyalty->redeemable($user, $subtotal - ($coupon ? $coupon->discountFor($subtotal) : 0.0));
            }
            $q = $this->pricing->quote($subtotal, $coupon, $rate, $points['discount']);

            $order = Order::create([
                'number' => 'TMP-'.Str::random(12), 'public_token' => Order::newToken(), 'user_id' => $user?->id,
                'status' => 'pending', 'payment_status' => 'unpaid', 'payment_method' => $gateway->key(),
                'shipping_method' => $rate->name, 'is_pickup' => $rate->is_pickup,
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
                'address_line' => $rate->is_pickup ? null : $data['address_line'], 'city' => $data['city'] ?? null,
                'area' => $data['area'] ?? null, 'postal_code' => $data['postal_code'] ?? null, 'notes' => $data['notes'] ?? null,
                'subtotal' => $q['subtotal'], 'discount' => $q['discount'], 'points_redeemed' => $points['points'], 'points_discount' => $q['points_discount'], 'locale' => app()->getLocale(), 'shipping_cost' => $q['shipping'], 'tax' => $q['tax'], 'total' => $q['total'],
                'tax_rate' => $q['tax_rate'], 'tax_inclusive' => $q['tax_inclusive'], 'currency' => (string) setting('store.currency', 'PKR'),
                'coupon_code' => $coupon?->code, 'requires_prescription' => $rx, 'prescription_status' => $rx ? 'pending' : null, 'placed_at' => now(),
                'slot_date' => $slot ? $data['slot_date'] : null, 'delivery_slot_id' => $slot?->id, 'slot_label' => $slot?->displayLabel(),
                'eta_at' => $this->eta($rate, $lines),
            ]);

            $order->update(['number' => Order::numberFor($order->id)]);

            foreach ($lines as $l) {
                OrderItem::create([
                    'order_id' => $order->id, 'product_id' => $l['product']->id, 'variant_id' => $l['variant']?->id,
                    'name' => $l['product']->name, 'variant_name' => $l['variant']?->name, 'sku' => $l['variant']?->sku ?: $l['product']->sku,
                    'options' => $l['options']->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'price' => (float) $o->price_delta])->all() ?: null,
                    'unit_price' => $l['unit'], 'qty' => $l['qty'], 'total' => $l['total'],
                ]);
                if ($l['product']->track_stock) {
                    try {
                        $this->stock->adjust($l['product'], -$l['qty'], 'sold', $l['variant'], [], __('Order :number', ['number' => $order->number]), $user?->id);
                    } catch (ValidationException $e) {
                        throw ValidationException::withMessages(['cart' => __('":name" just went out of stock. Please update your cart.', ['name' => $l['product']->trans('name')])]);
                    }
                }
            }

            $coupon?->increment('used_count');

            if ($user && $points['points'] > 0) {
                $this->loyalty->record($user, -$points['points'], 'redeem', $order, __('Order :number', ['number' => $order->number]));
            }
            // a reminder e-mail for this cart is no longer needed
            AbandonedCheckout::where('email', strtolower($data['email']))->whereNull('recovered_at')->update(['recovered_at' => now()]);

            foreach ($prescriptions as $file) {
                $order->addMedia($file)->toMediaCollection('prescriptions');
            }

            if ($user) {
                $user->forceFill(['phone' => $data['phone'], 'default_address' => array_filter([
                    'address_line' => $data['address_line'] ?? null, 'city' => $data['city'] ?? null, 'area' => $data['area'] ?? null, 'postal_code' => $data['postal_code'] ?? null,
                ])])->save();
            }

            $this->orders->log($order, 'placed', 'pending', null, $user);

            return $order;
        });

        $this->cart->clear();

        return $order;
    }
}
