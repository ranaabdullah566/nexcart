<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PrescriptionReviewed;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The pharmacist's queue: look at the uploaded prescription, approve it or send it back with a reason. */
class PrescriptionController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): View
    {
        $this->guard('prescriptions.view');

        $status = $request->query('status', 'pending');
        abort_unless(in_array($status, ['pending', 'approved', 'rejected', 'all'], true), 404);

        $query = Order::where('requires_prescription', true)->withCount('items')->with('prescriptionReviewer');
        $status === 'all' ? $query->latest('id') : $query->where('prescription_status', $status)->oldest('id');

        return view('admin.prescriptions.index', [
            'orders' => $query->limit(500)->get(),
            'status' => $status,
            'counts' => Order::where('requires_prescription', true)->selectRaw('prescription_status s, count(*) n')->groupBy('prescription_status')->pluck('n', 's'),
        ]);
    }

    public function show(Order $order): View
    {
        $this->guard('prescriptions.view');
        abort_unless($order->requires_prescription, 404);

        return view('admin.prescriptions.show', ['order' => $order->load(['items.product', 'prescriptionReviewer'])]);
    }

    public function review(Request $request, Order $order): RedirectResponse
    {
        $this->guard('prescriptions.verify');
        abort_unless($order->requires_prescription && $order->isOpen(), 404);

        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])], 'note' => ['nullable', 'string', 'max:190']]);
        $this->orders->reviewPrescription($order, $data['decision'], $data['note'] ?? null, $request->user());

        try {
            Mail::to($order->email)->locale($order->locale ?: app()->getLocale())->send(new PrescriptionReviewed($order->fresh()));
        } catch (\Throwable $e) {
            Log::warning('Prescription e-mail failed for '.$order->number.': '.$e->getMessage());
        }

        return redirect()->route('admin.prescriptions.index')->with('success', $data['decision'] === 'approved' ? __('Prescription approved') : __('Prescription sent back to the customer'));
    }

    private function guard(string $permission): void
    {
        abort_unless(BusinessProfile::hasModule('prescriptions'), 404);
        $this->authorizeTo($permission);
    }
}
