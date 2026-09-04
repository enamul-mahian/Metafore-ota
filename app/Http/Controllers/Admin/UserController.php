<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = trim((string) $request->query('role', ''));
        $verification = trim((string) $request->query('verification', ''));

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($role !== '', fn ($query) => $query->role($role))
            ->when($verification === 'verified', fn ($query) => $query->whereNotNull('email_verified_at'))
            ->when($verification === 'unverified', fn ($query) => $query->whereNull('email_verified_at'))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
            'filters' => ['search' => $search, 'role' => $role, 'verification' => $verification],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.create', [
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $data = $request->validated();
        $role = $this->guardRoleAssignment($actor, $data['role']);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->syncRoles([$role]);

        return redirect()->route('admin.users.show', $user)->with('status', 'User created successfully.');
    }

    public function show(User $user): View
    {
        $user->load('roles');

        return view('admin.users.show', compact('user'));
    }

    public function edit(Request $request, User $user): View
    {
        $this->guardUserTarget($request->user(), $user);

        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function update(SaveUserRequest $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $data = $request->validated();
        $this->guardUserTarget($actor, $user);
        $role = $this->guardRoleAssignment($actor, $data['role']);

        $currentRole = $user->getRoleNames()->first();

        if ($actor->is($user) && $currentRole !== $role->name) {
            abort(422, 'You cannot change your own role from User Management.');
        }

        $payload = ['name' => $data['name'], 'email' => $data['email']];
        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->syncRoles([$role]);

        return redirect()->route('admin.users.show', $user)->with('status', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->guardUserTarget($actor, $user);

        if ($actor->is($user)) {
            abort(422, 'You cannot delete your own account.');
        }

        if ($user->hasRole('super-admin')) {
            abort(403, 'Super Admin accounts cannot be deleted here.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted successfully.');
    }

    private function guardUserTarget(User $actor, User $target): void
    {
        if ($actor->hasRole('super-admin')) {
            return;
        }

        if ($target->hasRole('super-admin')) {
            abort(403, 'Only Super Admin can manage a Super Admin account.');
        }

        $actorPermissions = $actor->getAllPermissions()->pluck('name');
        $targetPermissions = $target->getAllPermissions()->pluck('name');

        if ($targetPermissions->diff($actorPermissions)->isNotEmpty()) {
            abort(403, 'You cannot manage a user with permissions beyond your own authority.');
        }
    }

    private function guardRoleAssignment(User $actor, string $roleName): Role
    {
        $role = Role::findByName($roleName, 'web');
        if ($actor->hasRole('super-admin')) {
            return $role;
        }
        if ($role->name === 'super-admin') {
            abort(403, 'Only Super Admin can assign the Super Admin role.');
        }

        $actorPermissions = $actor->getAllPermissions()->pluck('name');
        $rolePermissions = $role->permissions()->pluck('name');
        if ($rolePermissions->diff($actorPermissions)->isNotEmpty()) {
            abort(403, 'You cannot assign a role with permissions you do not have.');
        }

        return $role;
    }

    private function assignableRoles(User $actor): Collection
    {
        $roles = Role::query()->where('guard_name', 'web')->with('permissions')->orderBy('name')->get();
        if ($actor->hasRole('super-admin')) {
            return $roles;
        }
        $actorPermissions = $actor->getAllPermissions()->pluck('name');

        return $roles->reject(fn (Role $role): bool => $role->name === 'super-admin' || $role->permissions->pluck('name')->diff($actorPermissions)->isNotEmpty())->values();
    }
}
