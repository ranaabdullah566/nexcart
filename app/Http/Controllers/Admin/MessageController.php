<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Inbox for the Contact page, plus the newsletter list. */
class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeTo('storefront.manage');
        $status = $request->query('status', 'new');
        abort_unless(in_array($status, [...ContactMessage::STATUSES, 'all'], true), 404);

        $q = ContactMessage::query();
        $status === 'all' ? $q->latest('id') : $q->where('status', $status)->oldest('id');

        return view('admin.messages.index', [
            'messages' => $q->limit(500)->get(), 'status' => $status,
            'counts' => ContactMessage::selectRaw('status s, count(*) n')->groupBy('status')->pluck('n', 's'),
            'subscribers' => Subscriber::where('is_active', true)->count(),
        ]);
    }

    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $data = $request->validate(['status' => ['required', Rule::in(ContactMessage::STATUSES)]]);
        $message->update($data);

        return back()->with('success', __('Saved'));
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $this->authorizeTo('storefront.manage');
        $message->delete();

        return back()->with('success', __('Message deleted'));
    }

    /** Active subscribers as CSV (formula-safe). */
    public function subscribers(): StreamedResponse
    {
        $this->authorizeTo('storefront.manage');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'subscribed_at']);
            Subscriber::where('is_active', true)->orderBy('id')->each(function ($s) use ($out) {
                fputcsv($out, [preg_match('/^[=+\-@]/', $s->email) ? "'".$s->email : $s->email, $s->created_at?->toDateTimeString()]);
            });
            fclose($out);
        }, 'subscribers-'.date('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
