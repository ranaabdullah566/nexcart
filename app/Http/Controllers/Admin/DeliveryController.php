<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Rider console: my deliveries, with one-tap "picked up" / "delivered". Managers see every assigned order. */
class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $this->guard('deliveries.view');
        $user = $request->user();

        $orders = Order::with(['items', 'rider'])->whereNotNull('rider_id')->whereIn('status', ['confirmed', 'preparing', 'ready', 'out_for_delivery'])
            ->when(! $user->can('orders.update') || $user->hasRole('rider'), fn ($q) => $q->where('rider_id', $user->id))
            ->orderByRaw("case status when 'out_for_delivery' then 0 when 'ready' then 1 else 2 end")->oldest('placed_at')->get();

        return view('admin.deliveries.index', ['orders' => $orders, 'mine' => $user->hasRole('rider')]);
    }

    public function advance(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $this->guard('deliveries.update');
        $user = $request->user();

        // a rider can only move orders assigned to them
        abort_unless($order->rider_id === $user->id || ($user->can('orders.update') && ! $user->hasRole('rider')), 403);
        $to = match ($order->status) {
            'ready' => 'out_for_delivery',
            'out_for_delivery' => 'completed',
            default => null,
        };
        abort_if($to === null || $order->is_pickup, 422);

        $orders->transition($order, $to, $user);

        return redirect()->route('admin.deliveries.index')->with('success', $to === 'completed' ? __('Delivered — thank you!') : __('On the way'));
    }

    private function guard(string $permission): void
    {
        abort_unless(BusinessProfile::hasModule('riders'), 404);
        $this->authorizeTo($permission);
    }
}
