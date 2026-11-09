<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\ContactMessage;
use App\Models\Subscriber;
use App\Rules\Recaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Guest-friendly: contact form and newsletter sign-up (no account needed). */
class ContactController extends Controller
{
    public function show(Request $request): View
    {
        $u = $request->user();

        return view('store.contact', ['defaults' => ['name' => $u?->name, 'email' => $u?->email, 'phone' => $u?->phone]]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'g-recaptcha-response' => Recaptcha::rules(),
        ]);

        $m = ContactMessage::create(['user_id' => $request->user()?->id] + collect($data)->except('g-recaptcha-response')->all());
        Alert::broadcast('storefront.manage', __('New message from :name', ['name' => $m->name]).($m->subject ? ': '.$m->subject : ''), route('admin.messages.index'));

        return redirect()->route('store.contact')->with('contact_sent', $m->name);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);
        Subscriber::signUp($data['email']);

        // same answer whether or not the address was already on the list
        return back()->with('notice', __('Thanks! You are on the list for health tips and offers.'));
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $s = Subscriber::where('token', $token)->first();
        $s?->update(['is_active' => false, 'unsubscribed_at' => now()]);

        return redirect()->route('store.home')->with('notice', __('You have been unsubscribed.'));
    }
}
