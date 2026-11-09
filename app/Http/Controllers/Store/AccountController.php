<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PrescriptionRequest;
use App\Models\Review;
use App\Models\UserAddress;
use App\Rules\StrongPassword;
use App\Rules\UniqueActive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The signed-in customer's area: dashboard, orders, addresses, prescriptions, reviews, profile. */
class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $orders = Order::where('user_id', $user->id)->withSum('items as items_qty', 'qty')->latest('id')->get();

        return view('store.account.dashboard', [
            'user' => $user, 'orders' => $orders,
            'recent' => $orders->take(3),
            'rx' => PrescriptionRequest::where('user_id', $user->id)->latest('id')->limit(3)->get(),
            'addressCount' => $user->addresses()->count(),
        ]);
    }

    public function orders(Request $request): View
    {
        $filter = in_array($request->query('show'), ['open', 'done', 'cancelled'], true) ? $request->query('show') : 'all';
        $query = Order::where('user_id', $request->user()->id)->withSum('items as items_qty', 'qty')->latest('id');
        match ($filter) {
            'open' => $query->whereNotIn('status', ['completed', 'cancelled', 'refunded']),
            'done' => $query->where('status', 'completed'),
            'cancelled' => $query->whereIn('status', ['cancelled', 'refunded']),
            default => null,
        };

        return view('store.account.orders', ['user' => $request->user(), 'orders' => $query->paginate(10)->withQueryString(), 'filter' => $filter]);
    }

    public function addresses(Request $request): View
    {
        return view('store.account.addresses', ['user' => $request->user(), 'addresses' => $request->user()->addresses, 'editing' => null]);
    }

    public function editAddress(Request $request, UserAddress $address): View
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        return view('store.account.addresses', ['user' => $request->user(), 'addresses' => $request->user()->addresses, 'editing' => $address]);
    }

    public function saveAddress(Request $request, ?UserAddress $address = null): RedirectResponse
    {
        $user = $request->user();
        if ($address) {
            abort_unless($address->user_id === $user->id, 404);
        } elseif ($user->addresses()->count() >= UserAddress::MAX_PER_USER) {
            return back()->withErrors(['label' => __('You can save up to :n addresses.', ['n' => UserAddress::MAX_PER_USER])]);
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:40'], 'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9+\-()\s]{6,}$/'],
            'address_line' => ['required', 'string', 'max:255'], 'city' => ['required', 'string', 'max:120'],
            'area' => ['nullable', 'string', 'max:120'], 'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $address ??= new UserAddress(['user_id' => $user->id]);
        $address->fill($data)->save();
        if ($request->boolean('is_default') || $user->addresses()->count() === 1) {
            $address->setRelation('user', $user)->makeDefault();
        }

        return redirect()->route('store.account.addresses')->with('success', __('Address saved'));
    }

    public function defaultAddress(Request $request, UserAddress $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->setRelation('user', $request->user())->makeDefault();

        return back()->with('success', __('Default address updated'));
    }

    public function deleteAddress(Request $request, UserAddress $address): RedirectResponse
    {
        $user = $request->user();
        abort_unless($address->user_id === $user->id, 404);
        $wasDefault = $address->is_default;
        $address->delete();
        if ($wasDefault) {
            ($next = $user->addresses()->first()) ? $next->setRelation('user', $user)->makeDefault() : $user->forceFill(['default_address' => null])->save();
        }

        return redirect()->route('store.account.addresses')->with('success', __('Address removed'));
    }

    public function prescriptions(Request $request): View
    {
        return view('store.account.prescriptions', [
            'user' => $request->user(),
            'requests' => PrescriptionRequest::where('user_id', $request->user()->id)->with('media')->latest('id')->get(),
        ]);
    }

    /** A customer can download their own prescription photos (they live on a private disk). */
    public function prescriptionFile(Request $request, PrescriptionRequest $prescription, int $media): StreamedResponse
    {
        abort_unless($prescription->user_id === $request->user()->id, 404);
        $file = $prescription->getMedia('files')->firstWhere('id', $media);
        abort_if(! $file, 404);

        return $file->toResponse($request, 'inline');
    }

    public function reviews(Request $request): View
    {
        return view('store.account.reviews', [
            'user' => $request->user(),
            'reviews' => Review::where('user_id', $request->user()->id)->with('product')->latest('id')->get(),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('store.account.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', new UniqueActive('users', 'email', $user->id, true)],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-()\s]{6,}$/'],
            'password' => ['nullable', 'string', 'confirmed', StrongPassword::rule()],
            'current_password' => ['nullable', 'string', 'max:200'],
        ]);

        // changing the email or password is how an account gets taken over — it needs the current password
        $sensitive = filled($data['password'] ?? null) || strcasecmp($data['email'], $user->email) !== 0;
        if ($sensitive && ! Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
            throw ValidationException::withMessages(['current_password' => __('Enter your current password to change your email or password.')]);
        }

        $user->fill(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null]);
        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }
        $user->save();

        return redirect()->route('store.account.profile')->with('success', __('Profile saved'));
    }
}
