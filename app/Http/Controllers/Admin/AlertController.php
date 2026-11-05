<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The alerts inbox: everything the system told this person, kept after the toast disappears. */
class AlertController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeTo('alerts.view');
        $filter = in_array($request->query('show'), ['unread', 'success', 'error', 'warning', 'info'], true) ? $request->query('show') : 'all';

        $q = Alert::where('user_id', $request->user()->id)->latest('id');
        match ($filter) {
            'unread' => $q->whereNull('read_at'),
            'all' => null,
            default => $q->where('type', $filter),
        };

        return view('admin.alerts.index', ['alerts' => $q->paginate(30)->withQueryString(), 'filter' => $filter, 'unread' => Alert::where('user_id', $request->user()->id)->whereNull('read_at')->count()]);
    }

    public function read(Request $request, int $alert): RedirectResponse
    {
        $this->authorizeTo('alerts.view');
        $a = Alert::where('user_id', $request->user()->id)->findOrFail($alert);
        $a->update(['read_at' => $a->read_at ?? now()]);

        return $a->url && str_starts_with($a->url, url('/')) ? redirect($a->url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->authorizeTo('alerts.view');
        Alert::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }

    public function destroy(Request $request, int $alert): RedirectResponse
    {
        $this->authorizeTo('alerts.view');
        Alert::where('user_id', $request->user()->id)->whereKey($alert)->delete();

        return back();
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->authorizeTo('alerts.view');
        Alert::where('user_id', $request->user()->id)->delete();

        return back()->with('success', __('Alerts cleared'));
    }
}
