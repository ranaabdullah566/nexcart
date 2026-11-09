<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\PrescriptionRequest;
use App\Support\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrescriptionRequestController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless(BusinessProfile::hasModule('prescriptions'), 404);
        $u = $request->user();

        return view('store.prescription-upload', [
            'defaults' => ['name' => $u?->name, 'phone' => $u?->phone, 'email' => $u?->email, 'city' => $u?->default_address['city'] ?? null],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(BusinessProfile::hasModule('prescriptions'), 404);

        $data = $request->validate([
            'name' => ['required', new \App\Rules\NoMarkup, 'string', 'max:120'],
            'phone' => ['required', 'string', 'min:7', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'email' => ['nullable', 'email', 'max:190'],
            'city' => ['nullable', new \App\Rules\NoMarkup, 'string', 'max:120'],
            'notes' => ['nullable', new \App\Rules\NoMarkup, 'string', 'max:500'],
            'files' => ['required', 'array', 'min:1', 'max:5'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $req = PrescriptionRequest::create(['user_id' => $request->user()?->id] + collect($data)->except('files')->all());
        foreach ($request->file('files') as $file) {
            $req->addMedia($file)->toMediaCollection('files');
        }

        Alert::broadcast('prescriptions.view', __('New prescription from :name (:phone)', ['name' => $req->name, 'phone' => $req->phone]), route('admin.prescription-requests.index'));

        return redirect()->route('store.prescription.upload')->with('rx_sent', $req->name);
    }
}
