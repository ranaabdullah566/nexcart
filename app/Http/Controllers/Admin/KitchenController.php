<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Live kitchen board. The page re-fetches the board every few seconds and beeps when a new order lands. */
class KitchenController extends Controller
{
    public function index(): View
    {
        $this->guard();

        return view('admin.kitchen.index', $this->board());
    }

    public function feed(): View
    {
        $this->guard();

        return view('admin.kitchen._board', $this->board());
    }

    public function advance(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        $this->guard();
        $this->authorizeTo('orders.update');

        $next = $order->nextStatuses()[0] ?? null;
        // the kitchen hands food over at the counter; delivery runs through the rider, never the kitchen
        abort_if($next === null || $next === 'cancelled' || ($next === 'completed' && ! $order->is_pickup) || ($next === 'out_for_delivery' && $order->status === 'ready'), 422);

        $orders->transition($order, $next, $request->user());

        return redirect()->route('admin.kitchen.index');
    }

    private function guard(): void
    {
        abort_unless(BusinessProfile::hasModule('kitchen_display'), 404);
        $this->authorizeTo('orders.view');
    }

    private function board(): array
    {
        $orders = Order::with('items')->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery'])
            ->with('rider')->oldest('placed_at')->limit(80)->get();

        return [
            'columns' => [
                'new' => $orders->whereIn('status', ['pending', 'confirmed'])->values(),
                'preparing' => $orders->where('status', 'preparing')->values(),
                'ready' => $orders->whereIn('status', ['ready', 'out_for_delivery'])->values(),
            ],
            'newCount' => $orders->where('status', 'pending')->count(),
        ];
    }
}
