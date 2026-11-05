<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Rules\UniqueActive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('staff.view');

        return view('admin.staff.index', [
            'staff' => User::with('roles')->whereHas('roles')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeTo('staff.create');

        return view('admin.staff.create', ['roles' => Role::orderBy('name')->pluck('name')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('staff.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', new UniqueActive('users', 'email', null, true)],
            'role' => ['required', Rule::in(Role::pluck('name')->all())],
            'password' => ['required', 'string', 'confirmed', StrongPassword::rule()],
        ]);

        $this->guardOwnerRole($request, $data['role']);

        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $user->assignRole($data['role']);

        return redirect()->route('admin.staff.index')->with('success', __('Staff member added'));
    }

    public function edit(User $staff): View
    {
        $this->authorizeTo('staff.update');

        return view('admin.staff.edit', ['staff' => $staff, 'roles' => Role::orderBy('name')->pluck('name')]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $this->authorizeTo('staff.update');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', new UniqueActive('users', 'email', $staff->id, true)],
            'role' => ['required', Rule::in(Role::pluck('name')->all())],
            'password' => ['nullable', 'string', 'confirmed', StrongPassword::rule()],
        ]);

        $this->guardOwnerRole($request, $data['role'], $staff);

        $staff->fill(['name' => $data['name'], 'email' => $data['email']]);
        if (filled($data['password'] ?? null)) {
            $staff->password = $data['password'];
        }
        $staff->save();
        $staff->syncRoles([$data['role']]);

        return redirect()->route('admin.staff.index')->with('success', __('Staff member updated'));
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        $this->authorizeTo('staff.delete');

        if ($staff->is($request->user())) {
            return back()->with('error', __('You cannot delete your own account.'));
        }
        if ($staff->hasRole('owner') && User::role('owner')->count() <= 1) {
            return back()->with('error', __('The last owner cannot be deleted.'));
        }

        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', __('Staff member deleted'));
    }

    /** Only an owner may grant the owner role, or change an owner's role. */
    private function guardOwnerRole(Request $request, string $role, ?User $target = null): void
    {
        $actorIsOwner = $request->user()->hasRole('owner');

        abort_if(! $actorIsOwner && ($role === 'owner' || $target?->hasRole('owner')), 403);

        if ($target && $target->hasRole('owner') && $role !== 'owner' && User::role('owner')->count() <= 1) {
            abort(422, __('The last owner cannot lose the owner role.'));
        }
    }
}
