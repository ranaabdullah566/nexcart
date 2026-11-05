<?php

namespace App\Services;

use App\Models\LoyaltyEntry;
use App\Models\Order;
use App\Models\User;

/**
 * Points customers earn on completed orders and can spend at checkout.
 * The ledger is the only truth: balance = sum of entries, nothing is ever edited in place.
 */
class Loyalty
{
    public function enabled(): bool
    {
        return (bool) setting('growth.loyalty', false) && $this->earnPer() > 0 && $this->pointValue() > 0;
    }

    /** Spend this much to earn one point. */
    public function earnPer(): float
    {
        return max(0.0, (float) setting('growth.earn_per', 100));
    }

    /** What one point is worth when redeemed. */
    public function pointValue(): float
    {
        return max(0.0, (float) setting('growth.point_value', 1));
    }

    public function minRedeem(): int
    {
        return max(1, (int) setting('growth.min_redeem', 50));
    }

    public function balance(User $user): int
    {
        return max(0, (int) LoyaltyEntry::where('user_id', $user->id)->sum('points'));
    }

    /** How many points to use for an order and the discount they give, never more than the goods cost. */
    public function redeemable(User $user, float $payable): array
    {
        if (! $this->enabled()) {
            return ['points' => 0, 'discount' => 0.0];
        }
        $balance = $this->balance($user);
        $points = min($balance, (int) floor($payable / $this->pointValue()));

        return $points >= $this->minRedeem() ? ['points' => $points, 'discount' => round($points * $this->pointValue(), 2)] : ['points' => 0, 'discount' => 0.0];
    }

    public function record(User $user, int $points, string $type, ?Order $order = null, ?string $note = null): void
    {
        if ($points !== 0) {
            LoyaltyEntry::create(['user_id' => $user->id, 'order_id' => $order?->id, 'points' => $points, 'type' => $type, 'note' => $note]);
        }
    }

    /** Points for a completed order — once, and only for account holders. */
    public function earn(Order $order): void
    {
        if (! $this->enabled() || ! $order->user_id || LoyaltyEntry::where('order_id', $order->id)->where('type', 'earn')->exists()) {
            return;
        }
        $spent = (float) $order->subtotal - (float) $order->discount - (float) $order->points_discount;
        $points = (int) floor(max(0, $spent) / $this->earnPer());

        $this->record($order->user, $points, 'earn', $order, __('Order :number', ['number' => $order->number]));
    }

    /** A cancelled order gives back the points it spent. */
    public function restore(Order $order): void
    {
        if ($order->user_id && $order->points_redeemed > 0 && ! LoyaltyEntry::where('order_id', $order->id)->where('type', 'restore')->exists()) {
            $this->record($order->user, $order->points_redeemed, 'restore', $order, __('Order :number cancelled', ['number' => $order->number]));
        }
    }

    /** A refunded order takes back the points it earned. */
    public function reverse(Order $order): void
    {
        $earned = (int) LoyaltyEntry::where('order_id', $order->id)->where('type', 'earn')->sum('points');
        if ($order->user_id && $earned > 0 && ! LoyaltyEntry::where('order_id', $order->id)->where('type', 'reverse')->exists()) {
            $this->record($order->user, -$earned, 'reverse', $order, __('Order :number refunded', ['number' => $order->number]));
        }
    }
}
