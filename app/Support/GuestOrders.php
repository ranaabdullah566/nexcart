<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;

/**
 * A guest's orders are remembered in their browser session, so that creating an account (or signing in)
 * right afterwards attaches those orders to the account. Only orders placed from THIS session are claimed —
 * never "every order with this e-mail address", which would let anyone read someone else's history.
 */
class GuestOrders
{
    private const KEY = 'guest_orders';

    public static function remember(Order $order): void
    {
        if ($order->user_id === null) {
            $ids = (array) session(self::KEY, []);
            $ids[] = $order->id;
            session([self::KEY => array_values(array_unique(array_slice($ids, -20))), 'guest_prefill' => ['name' => $order->name, 'email' => $order->email, 'phone' => $order->phone]]);
        }
    }

    public static function pending(): int
    {
        return Order::whereNull('user_id')->whereIn('id', (array) session(self::KEY, []))->count();
    }

    /** @return int number of orders attached */
    public static function claim(User $user): int
    {
        $ids = (array) session(self::KEY, []);
        if ($ids === []) {
            return 0;
        }
        $n = Order::whereNull('user_id')->whereIn('id', $ids)->update(['user_id' => $user->id]);
        session()->forget([self::KEY, 'guest_prefill']);

        return $n;
    }
}
