<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliverySlot;
use App\Support\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliverySlotController extends Controller
{
    public function index(): View
    {
        $this->guard();

        return view('admin.slots.index', [
            'slots' => DeliverySlot::orderBy('sort_order')->orderBy('starts_at')->get(),
            'daysAhead' => (int) setting('delivery.days_ahead', 3), 'cutoff' => (int) setting('delivery.cutoff_minutes', 60),
        ]);
    }

    public function create(): View
    {
        $this->guard();

        return view('admin.slots.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard();
        $this->save(new DeliverySlot(['sort_order' => (int) DeliverySlot::max('sort_order') + 1]), $request);

        return redirect()->route('admin.delivery-slots.index')->with('success', __('Time slot saved'));
    }

    public function edit(DeliverySlot $deliverySlot): View
    {
        $this->guard();

        return view('admin.slots.edit', ['slot' => $deliverySlot]);
    }

    public function update(Request $request, DeliverySlot $deliverySlot): RedirectResponse
    {
        $this->guard();
        $this->save($deliverySlot, $request);

        return redirect()->route('admin.delivery-slots.index')->with('success', __('Time slot saved'));
    }

    public function destroy(DeliverySlot $deliverySlot): RedirectResponse
    {
        $this->guard();
        $deliverySlot->delete();   // past orders keep their label snapshot

        return redirect()->route('admin.delivery-slots.index')->with('success', __('Time slot deleted'));
    }

    public function settings(Request $request): RedirectResponse
    {
        $this->guard();
        $data = $request->validate(['days_ahead' => ['required', 'integer', 'min:0', 'max:14'], 'cutoff_minutes' => ['required', 'integer', 'min:0', 'max:1440']]);
        setting()->setMany(['delivery.days_ahead' => $data['days_ahead'], 'delivery.cutoff_minutes' => $data['cutoff_minutes']]);

        return redirect()->route('admin.delivery-slots.index')->with('success', __('Settings saved'));
    }

    private function save(DeliverySlot $slot, Request $request): void
    {
        $time = ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'];
        $data = $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'starts_at' => $time,
            'ends_at' => [...$time, 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        $slot->fill($data + ['capacity' => null, 'is_active' => $request->boolean('is_active')])->save();
    }

    private function guard(): void
    {
        abort_unless(BusinessProfile::hasModule('delivery_slots'), 404);
        $this->authorizeTo('sales_settings.manage');
    }
}
