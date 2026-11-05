<?php

namespace App\Services\Payments;

class PaymentManager
{
    /** @return array<string, PaymentGateway> every known gateway, keyed by its key */
    public function all(): array
    {
        return collect([new CashOnDelivery, new BankTransfer, new StripeCheckout])->keyBy(fn ($g) => $g->key())->all();
    }

    /** @return array<string, PaymentGateway> gateways the store has switched on */
    public function enabled(): array
    {
        return array_filter($this->all(), fn (PaymentGateway $g) => $g->enabled());
    }

    public function find(string $key): ?PaymentGateway
    {
        return $this->enabled()[$key] ?? null;
    }
}
