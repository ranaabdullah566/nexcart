<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Rules\UniqueActive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Customers: people with a shop account (admins can add / edit / delete them) plus guests who only ever checked out. */
class CustomerController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('customers.view');

        $stats = Order::selectRaw("lower(email) as email, max(name) as name, max(phone) as phone, count(*) as orders, sum(case when status in ('cancelled','refunded') then 0 else total end) as spent, max(placed_at) as last_order")
            ->groupByRaw('lower(email)')->get()->keyBy('email');

        $accounts = User::doesntHave('roles')->orderBy('name')->get();
        $rows = $accounts->map(function (User $u) use ($stats) {
            $s = $stats->get(strtolower($u->email));

            return (object) ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'orders' => (int) ($s->orders ?? 0), 'spent' => (float) ($s->spent ?? 0), 'last_order' => $s->last_order ?? null, 'has_account' => true, 'joined' => $u->created_at];
        });
        $known = $accounts->pluck('email')->map('strtolower');
        $guests = $stats->reject(fn ($s, $email) => $known->contains($email))->map(fn ($s) => (object) ['id' => null, 'name' => $s->name, 'email' => $s->email, 'phone' => $s->phone, 'orders' => (int) $s->orders, 'spent' => (float) $s->spent, 'last_order' => $s->last_order, 'has_account' => false, 'joined' => null]);

        return view('admin.customers.index', ['customers' => $rows->concat($guests)->values()]);
    }

    public function create(Request $request): View
    {
        $this->authorizeTo('customers.create');

        return view('admin.customers.create', ['customer' => new User($request->only(['name', 'email', 'phone']))]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('customers.create');
        $data = $request->validate($this->rules(null, true));

        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'password' => $data['password'], 'default_address' => $this->address($data)]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.customers.index')->with('success', __('Customer added'));
    }

    public function edit(User $customer): View
    {
        $this->authorizeTo('customers.update');
        abort_if($customer->isStaff(), 404);

        return view('admin.customers.edit', ['customer' => $customer, 'orders' => Order::where('user_id', $customer->id)->orWhereRaw('lower(email) = ?', [strtolower($customer->email)])->latest('id')->limit(10)->get()]);
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        $this->authorizeTo('customers.update');
        abort_if($customer->isStaff(), 404);
        $data = $request->validate($this->rules($customer, false));

        $customer->fill(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null, 'default_address' => $this->address($data)]);
        if (filled($data['password'] ?? null)) {
            $customer->password = $data['password'];
        }
        $customer->save();

        return redirect()->route('admin.customers.index')->with('success', __('Customer updated'));
    }

    public function destroy(User $customer): RedirectResponse
    {
        $this->authorizeTo('customers.delete');
        abort_if($customer->isStaff(), 404);
        $customer->delete();   // soft delete: past orders keep their details

        return redirect()->route('admin.customers.index')->with('success', __('Customer deleted'));
    }

    private function rules(?User $customer, bool $creating): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', new UniqueActive('users', 'email', $customer?->id, true)],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-()\s]{6,}$/'],
            'address_line' => ['nullable', 'string', 'max:255'], 'city' => ['nullable', 'string', 'max:120'],
            'area' => ['nullable', 'string', 'max:120'], 'postal_code' => ['nullable', 'string', 'max:20'],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'confirmed', StrongPassword::rule()],
        ];
    }

    private function address(array $d): ?array
    {
        return array_filter(collect($d)->only(['address_line', 'city', 'area', 'postal_code'])->all()) ?: null;
    }
}
