<?php

namespace App\Services\Payments;

use App\Models\Order;

class BankTransfer implements PaymentGateway
{
    public function key(): string
    {
        return 'bank';
    }

    public function label(): string
    {
        return __('Bank transfer');
    }

    public function description(): ?string
    {
        return __('Transfer the amount after placing your order. Details are shown on the next page.');
    }

    public function enabled(): bool
    {
        return (bool) setting('payments.bank', false) && filled(setting('payments.bank_instructions'));
    }

    public function instructions(): string
    {
        return (string) setting('payments.bank_instructions', '');
    }

    public function start(Order $order): ?string
    {
        return null;
    }
}
