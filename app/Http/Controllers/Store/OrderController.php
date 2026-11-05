<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\Payments\BankTransfer;
use App\Services\Payments\StripeCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** Order page for guests and customers alike: the long random token is the key. */
    public function show(string $number, string $token): View
    {
        $order = $this->find($number, $token)->load(['items', 'events']);

        return view('store.order', ['order' => $order, 'bank' => $order->payment_method === 'bank' ? (new BankTransfer)->instructions() : null]);
    }

    public function invoice(string $number, string $token): View
    {
        return view('store.invoice', ['order' => $this->find($number, $token)->load('items')]);
    }

    public function trackForm(): View
    {
        return view('store.track');
    }

    public function trackFind(Request $request): RedirectResponse
    {
        $data = $request->validate(['number' => ['required', 'string', 'max:30'], 'email' => ['required', 'email']]);

        $order = Order::where('number', strtoupper(trim($data['number'])))->first();
        if (! $order || strcasecmp($order->email, $data['email']) !== 0) {
            return back()->withInput()->withErrors(['number' => __('We could not find an order with those details.')]);
        }

        return redirect($order->trackUrl());
    }

    /** Re-open the card payment page for an unpaid Stripe order. */
    public function pay(string $number, string $token): RedirectResponse
    {
        $order = $this->find($number, $token);
        abort_unless($order->payment_method === 'stripe' && $order->payment_status === 'unpaid' && $order->isOpen(), 404);

        try {
            return redirect()->away((new StripeCheckout)->start($order));
        } catch (\Throwable $e) {
            report($e);

            return redirect($order->trackUrl())->with('error', __('We could not open the payment page. Please try again in a moment.'));
        }
    }

    public function stripeReturn(Request $request, string $number, string $token, OrderService $orders): RedirectResponse
    {
        $order = $this->find($number, $token);
        $stripe = new StripeCheckout;

        if ($order->payment_method === 'stripe' && $order->payment_status !== 'paid' && $stripe->isPaid($order, (string) $request->query('session_id'))) {
            $orders->markPaid($order, $order->payment_reference);

            return redirect($order->trackUrl())->with('success', __('Payment received. Thank you!'));
        }

        return redirect($order->trackUrl())->with('error', __('We could not confirm your payment yet. If you were charged, contact us with your order number.'));
    }

    private function find(string $number, string $token): Order
    {
        $order = Order::where('number', $number)->firstOrFail();
        abort_unless(hash_equals($order->public_token, $token), 404);

        return $order;
    }
}
