<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGateway
{
    public function key(): string;

    public function label(): string;

    /** Short text under the option at checkout. */
    public function description(): ?string;

    public function enabled(): bool;

    /** Online gateways return a URL to send the customer to; offline ones return null. */
    public function start(Order $order): ?string;
}
