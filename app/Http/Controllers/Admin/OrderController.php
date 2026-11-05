<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Support\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): View
    {
        $this->authorizeTo('orders.view');

        $status = $request->query('status');
        abort_if($status !== null && ! in_array($status, [...Order::STATUSES, 'open'], true), 404);

        $query = Order::withCount('items')->latest('id');
        if ($status === 'open') {
            $query->whereNotIn('status', ['completed', 'cancelled', 'refunded']);
        } elseif ($status) {
            $query->where('status', $status);
        }

        $counts = Order::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.orders.index', [
            'orders' => $query->limit(1000)->get(),
            'status' => $status,
            'counts' => $counts,
            'open' => $counts->except(['completed', 'cancelled', 'refunded'])->sum(),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorizeTo('orders.view');

        return view('admin.orders.show', [
            'order' => $order->load(['items', 'events.user', 'user', 'rider']),
            'riders' => BusinessProfile::hasModule('riders') ? User::role('rider')->orderBy('name')->get() : collect(),
        ]);
    }

    public function status(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeTo('orders.update');
        $data = $request->validate(['status' => ['required', Rule::in(Order::STATUSES)], 'note' => ['nullable', 'string', 'max:190']]);

        if ($data['status'] === 'refunded') {
            $this->authorizeTo('orders.refund');
        }
        $this->orders->transition($order, $data['status'], $request->user(), $data['note'] ?? null);

        return redirect()->route('admin.orders.index')->with('success', __('Order :number updated', ['number' => $order->number]));
    }

    public function rider(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeTo('orders.update');
        abort_unless(BusinessProfile::hasModule('riders'), 404);

        $data = $request->validate(['rider_id' => ['nullable', 'integer', 'exists:users,id']]);
        $this->orders->assignRider($order, $data['rider_id'] ? User::findOrFail($data['rider_id']) : null, $request->user());

        return redirect()->route('admin.orders.index')->with('success', __('Rider updated'));
    }

    public function paid(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeTo('orders.update');
        abort_unless($order->payment_status === 'unpaid' && $order->isOpen(), 422);

        $data = $request->validate(['reference' => ['nullable', 'string', 'max:120']]);
        $this->orders->markPaid($order, $data['reference'] ?? null, $request->user());

        return redirect()->route('admin.orders.index')->with('success', __('Order :number marked as paid', ['number' => $order->number]));
    }

    /** Prescriptions live on a private disk; only staff who may review them can open one. */
    public function prescription(Order $order, int $media)
    {
        abort_unless(auth()->user()->can('prescriptions.view') || auth()->user()->can('orders.view'), 403);
        $file = $order->getMedia('prescriptions')->firstWhere('id', $media);
        abort_unless($file, 404);

        return response()->file($file->getPath(), ['Content-Type' => $file->mime_type, 'Content-Disposition' => 'inline; filename="'.addslashes($file->file_name).'"', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
