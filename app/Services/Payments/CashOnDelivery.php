<?php

namespace App\Services\Payments;

use App\Models\Order;

class CashOnDelivery implements PaymentGateway
{
    public function key(): string
    {
        return 'cod';
    }

    public function label(): string
    {
        return __('Cash on delivery');
    }

    public function description(): ?string
    {
        return __('Pay in cash when your order arrives or when you pick it up.');
    }

    public function enabled(): bool
    {
        return (bool) setting('payments.cod', true);
    }

    public function start(Order $order): ?string
    {
        return null;
    }
}
