<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Mail\OrderPlaced;
use App\Models\AbandonedCheckout;
use App\Models\Alert;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Rules\Recaptcha;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\Payments\PaymentManager;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cart, private CheckoutService $checkout, private PaymentManager $payments) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($this->cart->lines()->isEmpty()) {
            return redirect()->route('store.cart');
        }

        $user = $request->user();
        $addr = $user?->default_address ?? [];
        $city = old('city', $addr['city'] ?? null);

        $first = $this->checkout->quote($city, null, session('coupon'));
        $rateId = (int) old('shipping_rate_id', $first['rates']->first()?->id);
        $q = $this->checkout->quote($city, $rateId ?: null, session('coupon'));
        $lines = $this->cart->toArray();

        return view('store.checkout', [
            'lines' => $lines,
            'q' => $q,
            'defaults' => ['name' => $user?->name, 'email' => $user?->email, 'phone' => $user?->phone, 'address_line' => $addr['address_line'] ?? null, 'city' => $city, 'area' => $addr['area'] ?? null, 'postal_code' => $addr['postal_code'] ?? null],
            'cities' => ShippingZone::where('is_active', true)->pluck('cities')->flatten()->filter()->unique()->values(),
            'gateways' => $this->payments->enabled(),
            'needsRx' => $this->checkout->needsPrescription(),
            'slotDays' => app(SlotService::class)->enabled() ? app(SlotService::class)->days() : [],
            'user' => $user,
        ]);
    }

    /** Live order summary + delivery options while the customer types. */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate(['city' => ['nullable', 'string', 'max:120'], 'rate_id' => ['nullable', 'integer'], 'coupon' => ['nullable', 'string', 'max:40']]);

        $coupon = $data['coupon'] ?? null;
        $q = $this->checkout->quote($data['city'] ?? null, $data['rate_id'] ?? null, $coupon);

        // remember a coupon that works so a page reload keeps it; forget one that does not
        if (filled($coupon) && $q['coupon'] && ! $q['coupon_problem']) {
            session(['coupon' => $q['coupon']->code]);
        } elseif (! filled($coupon) || $q['coupon_problem'] || ! $q['coupon']) {
            session()->forget('coupon');
        }

        return response()->json($this->present($q));
    }

    public function place(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'g-recaptcha-response' => Recaptcha::rules(),
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+\-()\s]{6,}$/'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'area' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
            'shipping_rate_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string', 'max:24'],
            'coupon' => ['nullable', 'string', 'max:40'],
            'slot_date' => ['nullable', 'date_format:Y-m-d'],
            'delivery_slot_id' => ['nullable', 'integer'],
            'prescription' => ['nullable', 'array', 'max:5'],
            'prescription.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $order = $this->checkout->place($data, $request->user(), $request->file('prescription', []));
        session()->forget('coupon');

        $this->notify($order);

        $gateway = $this->payments->find($order->payment_method);
        try {
            if ($url = $gateway?->start($order)) {
                return redirect()->away($url);
            }
        } catch (\Throwable $e) {
            Log::error('Payment start failed for '.$order->number.': '.$e->getMessage());

            return redirect($order->trackUrl())->with('error', __('We could not open the payment page. Your order is saved — you can pay from this page or contact us.'));
        }

        return redirect($order->trackUrl())->with('placed', true);
    }

    /** Remember the cart of someone who typed their e-mail, so we can remind them if they never finish. */
    public function capture(Request $request, CartService $cart): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);
        $lines = $cart->lines();
        if (! setting('growth.abandoned', true) || $lines->isEmpty()) {
            return response()->json(['ok' => false]);
        }

        $email = strtolower($data['email']);
        if (AbandonedCheckout::where('email', $email)->where('opted_out', true)->exists()) {
            return response()->json(['ok' => false]);
        }

        $row = AbandonedCheckout::firstOrNew(['email' => $email, 'recovered_at' => null]);
        $row->forceFill([
            'token' => $row->token ?: Str::random(40),
            'items' => $lines->map(fn ($l) => ['product_id' => $l['product']->id, 'variant_id' => $l['variant']?->id, 'qty' => $l['qty']])->all(),
            'total' => $lines->sum('total'), 'items_count' => (int) $lines->sum('qty'), 'locale' => app()->getLocale(), 'last_seen_at' => now(),
        ] + ($row->exists ? [] : ['reminded_at' => null]))->save();

        return response()->json(['ok' => true]);
    }

    /** Link from the reminder e-mail: put the items back and go straight to checkout. */
    public function recover(string $token, CartService $cart): RedirectResponse
    {
        $row = AbandonedCheckout::where('token', $token)->firstOrFail();
        $added = 0;
        foreach ((array) $row->items as $i) {
            $product = Product::active()->find($i['product_id'] ?? 0);
            if (! $product) {
                continue;
            }
            try {
                $cart->add($product, $i['variant_id'] ?? null, (int) $i['qty']);
                $added++;
            } catch (ValidationException) {
                // sold out or no longer valid: skip it, the rest still comes back
            }
        }

        return $added ? redirect()->route('store.checkout')->with('notice', __('Welcome back! Your cart is ready.')) : redirect()->route('store.shop')->with('notice', __('Those items are no longer available.'));
    }

    public function stopReminders(string $token): RedirectResponse
    {
        AbandonedCheckout::where('token', $token)->firstOrFail()->update(['opted_out' => true]);
        AbandonedCheckout::where('email', AbandonedCheckout::where('token', $token)->value('email'))->update(['opted_out' => true]);

        return redirect()->route('store.home')->with('notice', __('Done — we will not send you cart reminders.'));
    }

    private function present(array $q): array
    {
        $rates = $q['rates']->map(fn ($r) => [
            'id' => $r->id, 'name' => $r->name, 'eta' => $r->eta, 'pickup' => $r->is_pickup,
            'price' => $r->costFor($q['subtotal']) > 0 ? money($r->costFor($q['subtotal'])) : __('Free'),
        ])->values();

        return [
            'subtotal' => money($q['subtotal']),
            'discount' => $q['discount'] > 0 ? '−'.money($q['discount']) : null,
            'shipping' => $q['rate'] ? ($q['shipping'] > 0 ? money($q['shipping']) : __('Free')) : null,
            'tax' => $q['tax'] > 0 ? money($q['tax']) : null,
            'tax_inclusive' => $q['tax_inclusive'],
            'total' => money($q['total']),
            'coupon' => $q['coupon'] && ! $q['coupon_problem'] ? ['code' => $q['coupon']->code, 'label' => $q['coupon']->label()] : null,
            'coupon_error' => $q['coupon_problem'] ?? $q['coupon_error'],
            'rates' => $rates,
            'rate_id' => $q['rate']?->id,
        ];
    }

    private function notify(Order $order): void
    {
        Alert::broadcast('orders.view', __('New order :n — :total', ['n' => $order->number, 'total' => money($order->total)]), route('admin.orders.show', $order), 'info', 'order');
        try {
            Mail::to($order->email)->send(new OrderPlaced($order));
            if (filled(setting('store.email'))) {
                Mail::to(setting('store.email'))->send(new OrderPlaced($order, true));
            }
        } catch (\Throwable $e) {
            Log::warning('Order e-mail failed for '.$order->number.': '.$e->getMessage());   // never block an order over e-mail
        }
    }
}
