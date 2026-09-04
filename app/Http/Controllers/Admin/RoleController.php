<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    private const SYSTEM_ROLES = [
        'super-admin',
        'admin',
        'customer',
    ];

    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->withCount(['permissions', 'users'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'permissions' => $this->permissions(),
        ]);
    }

    public function store(SaveRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (in_array($data['name'], self::SYSTEM_ROLES, true)) {
            abort(422, 'That role name is reserved.');
        }

        $role = Role::query()->create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('status', 'Role created successfully.');
    }

    public function show(Role $role): View
    {
        $this->ensureWebRole($role);

        return view('admin.roles.show', [
            'role' => $role->load('permissions')->loadCount('users'),
        ]);
    }

    public function edit(Role $role): View
    {
        $this->ensureWebRole($role);

        if ($role->name === 'super-admin') {
            abort(403, 'Super Admin role permissions are protected.');
        }

        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => $this->permissions(),
            'isSystemRole' => in_array($role->name, self::SYSTEM_ROLES, true),
        ]);
    }

    public function update(SaveRoleRequest $request, Role $role): RedirectResponse
    {
        $this->ensureWebRole($role);

        if ($role->name === 'super-admin') {
            abort(403, 'Super Admin role permissions are protected.');
        }

        $data = $request->validated();
        $isSystemRole = in_array($role->name, self::SYSTEM_ROLES, true);

        if ($isSystemRole && $data['name'] !== $role->name) {
            abort(422, 'System role names cannot be changed.');
        }

        if (! $isSystemRole && in_array($data['name'], self::SYSTEM_ROLES, true)) {
            abort(422, 'That role name is reserved.');
        }

        $role->update([
            'name' => $data['name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('status', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->ensureWebRole($role);

        if (in_array($role->name, self::SYSTEM_ROLES, true)) {
            abort(403, 'System roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            abort(422, 'Role cannot be deleted while users are assigned to it.');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role deleted successfully.');
    }

    private function permissions()
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();
    }

    private function ensureWebRole(Role $role): void
    {
        abort_unless($role->guard_name === 'web', 404);
    }
}
