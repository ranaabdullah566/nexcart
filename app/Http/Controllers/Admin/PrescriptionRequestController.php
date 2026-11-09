<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrescriptionRequest;
use App\Support\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Prescriptions sent without an order — the pharmacy calls the customer back. */
class PrescriptionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->guard('prescriptions.view');
        $status = $request->query('status', 'new');
        abort_unless(in_array($status, [...PrescriptionRequest::STATUSES, 'all'], true), 404);

        $query = PrescriptionRequest::with('handler')->withCount('media');
        $status === 'all' ? $query->latest('id') : $query->where('status', $status)->oldest('id');

        return view('admin.prescription-requests.index', [
            'requests' => $query->limit(500)->get(),
            'status' => $status,
            'counts' => PrescriptionRequest::selectRaw('status s, count(*) n')->groupBy('status')->pluck('n', 's'),
        ]);
    }

    public function file(PrescriptionRequest $prescriptionRequest, int $media)
    {
        $this->guard('prescriptions.view');
        $file = $prescriptionRequest->getMedia('files')->firstWhere('id', $media);
        abort_unless($file, 404);

        return response()->file($file->getPath(), ['Content-Type' => $file->mime_type, 'Content-Disposition' => 'inline; filename="'.addslashes($file->file_name).'"', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function update(Request $request, PrescriptionRequest $prescriptionRequest): RedirectResponse
    {
        $this->guard('prescriptions.verify');
        $data = $request->validate(['status' => ['required', Rule::in(PrescriptionRequest::STATUSES)]]);
        $prescriptionRequest->update($data + ['handled_by' => $request->user()->id, 'handled_at' => now()]);

        return back()->with('success', __('Saved'));
    }

    public function destroy(PrescriptionRequest $prescriptionRequest): RedirectResponse
    {
        $this->guard('prescriptions.verify');
        $prescriptionRequest->delete();

        return back()->with('success', __('Moved to the Recycle Bin'));
    }

    private function guard(string $permission): void
    {
        abort_unless(BusinessProfile::hasModule('prescriptions'), 404);
        $this->authorizeTo($permission);
    }
}
