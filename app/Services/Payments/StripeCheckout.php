<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Stripe Checkout (hosted page). The secret key is stored encrypted in Store settings → Payments. */
class StripeCheckout implements PaymentGateway
{
    private const API = 'https://api.stripe.com/v1';

    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return __('Card (Stripe)');
    }

    public function description(): ?string
    {
        return __('Pay securely by card on Stripe’s page.');
    }

    public function enabled(): bool
    {
        return (bool) setting('payments.stripe', false) && $this->secret() !== null;
    }

    public static function storeSecret(?string $plain): void
    {
        setting()->set('payments.stripe_secret', filled($plain) ? Crypt::encryptString($plain) : null);
    }

    public function secret(): ?string
    {
        $raw = setting('payments.stripe_secret');
        if (! $raw) {
            return null;
        }
        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    public function start(Order $order): ?string
    {
        $res = Http::withBasicAuth($this->secret() ?? '', '')->asForm()->timeout(20)->post(self::API.'/checkout/sessions', [
            'mode' => 'payment',
            'client_reference_id' => $order->number,
            'customer_email' => $order->email,
            'success_url' => route('store.stripe.return', [$order->number, $order->public_token]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $order->trackUrl(),
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => strtolower($order->currency),
            'line_items[0][price_data][unit_amount]' => (int) round((float) $order->total * 100),
            'line_items[0][price_data][product_data][name]' => __('Order :number', ['number' => $order->number]),
        ]);

        if (! $res->successful() || ! $res->json('url')) {
            throw new RuntimeException('Stripe could not start the payment: '.($res->json('error.message') ?? $res->status()));
        }

        $order->update(['payment_reference' => $res->json('id')]);

        return $res->json('url');
    }

    /** Ask Stripe whether this session really was paid, for this order and this amount. */
    public function isPaid(Order $order, string $sessionId): bool
    {
        if ($order->payment_reference !== $sessionId) {
            return false;
        }
        $res = Http::withBasicAuth($this->secret() ?? '', '')->timeout(20)->get(self::API.'/checkout/sessions/'.urlencode($sessionId));

        return $res->successful()
            && $res->json('payment_status') === 'paid'
            && $res->json('client_reference_id') === $order->number
            && (int) $res->json('amount_total') === (int) round((float) $order->total * 100);
    }
}
