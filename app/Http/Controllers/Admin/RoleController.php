<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Database\Seeders\RolesSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Roles & permissions: build any role and tick exactly what it may do. The owner role is fixed (everything). */
class RoleController extends Controller
{
    /** Friendly names for permission groups. */
    public const LABELS = [
        'products' => 'Products', 'categories' => 'Categories', 'orders' => 'Orders', 'customers' => 'Customers', 'staff' => 'Staff',
        'settings' => 'Store settings', 'reports' => 'Reports', 'prescriptions' => 'Prescriptions', 'deliveries' => 'Deliveries',
        'inventory' => 'Inventory', 'catalog_settings' => 'Product fields & add-ons', 'storefront' => 'Storefront builder & theme',
        'sales_settings' => 'Coupons, shipping & slots', 'reviews' => 'Reviews', 'system' => 'System (backups, license, logs)',
        'activity' => 'Activity log', 'trash' => 'Recycle Bin', 'roles' => 'Roles & permissions',
    ];

    public function index(): View
    {
        $this->authorizeTo('roles.manage');

        return view('admin.roles.index', ['roles' => Role::withCount(['users', 'permissions'])->orderBy('id')->get(), 'total' => Permission::count()]);
    }

    public function create(): View
    {
        $this->authorizeTo('roles.manage');

        return view('admin.roles.create', $this->formData(new Role));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('roles.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z][A-Za-z0-9 _-]*$/', Rule::unique('roles', 'name')],
            'permissions' => ['nullable', 'array'], 'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);
        $role = Role::create(['name' => strtolower(trim($data['name'])), 'guard_name' => 'web']);
        $role->syncPermissions($this->safePermissions($data['permissions'] ?? []));
        Audit::log('role.created', ['role' => $role->name, 'permissions' => count($data['permissions'] ?? [])]);

        return redirect()->route('admin.roles.index')->with('success', __('Role saved'));
    }

    public function edit(Role $role): View
    {
        $this->authorizeTo('roles.manage');

        return view('admin.roles.edit', $this->formData($role));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeTo('roles.manage');
        abort_if($role->name === 'owner', 403, __('The owner role always has every permission.'));
        $data = $request->validate([
            'permissions' => ['nullable', 'array'], 'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);
        $before = $role->permissions->pluck('name')->sort()->values()->all();
        $role->syncPermissions($this->safePermissions($data['permissions'] ?? []));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $after = $role->permissions()->pluck('name')->sort()->values()->all();
        Audit::log('role.updated', ['role' => $role->name, 'added' => array_values(array_diff($after, $before)), 'removed' => array_values(array_diff($before, $after))]);

        return redirect()->route('admin.roles.index')->with('success', __('Role saved'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizeTo('roles.manage');
        if (in_array($role->name, ['owner', 'manager', 'staff', 'rider', 'pharmacist'], true)) {
            return back()->with('error', __('Built-in roles cannot be deleted.'));
        }
        if ($role->users()->exists()) {
            return back()->with('error', __('Move the people in this role to another role first.'));
        }
        $role->delete();
        Audit::log('role.deleted', ['role' => $role->name]);

        return redirect()->route('admin.roles.index')->with('success', __('Role deleted'));
    }

    private function formData(Role $role): array
    {
        $groups = [];
        foreach (RolesSeeder::PERMISSIONS as $group => $actions) {
            $groups[$group] = ['label' => self::LABELS[$group] ?? ucfirst($group), 'actions' => $actions];
        }

        return ['role' => $role, 'groups' => $groups, 'granted' => $role->exists ? $role->permissions->pluck('name')->all() : []];
    }

    /** Only permissions that exist; nobody can hand out roles.manage unless they hold it (owner always does). */
    private function safePermissions(array $names): array
    {
        return array_values(array_filter($names, fn ($n) => request()->user()->can($n)));
    }
}
