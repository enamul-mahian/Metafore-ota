@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    <x-admin.page-header
        title="Users"
        description="Manage verified accounts, role assignments, and access status."
        icon="U"
        eyebrow="Account management"
    >
        @can('users.manage')
            <a class="egh-button" href="{{ route('admin.users.create') }}">Create User</a>
        @endcan
    </x-admin.page-header>

    <form method="GET" action="{{ route('admin.users.index') }}" class="egh-card admin-filter-bar">
        <label class="admin-filter-field">
            <span>Search</span>
            <input name="search" value="{{ $filters['search'] }}" placeholder="Name or email">
        </label>

        <label class="admin-filter-field">
            <span>Role</span>
            <select name="role">
                <option value="">All roles</option>
                @foreach ($roles as $roleName)
                    <option value="{{ $roleName }}" @selected($filters['role'] === $roleName)>{{ $roleName }}</option>
                @endforeach
            </select>
        </label>

        <label class="admin-filter-field">
            <span>Verification</span>
            <select name="verification">
                <option value="">All accounts</option>
                <option value="verified" @selected($filters['verification'] === 'verified')>Verified</option>
                <option value="unverified" @selected($filters['verification'] === 'unverified')>Unverified</option>
            </select>
        </label>

        <button class="egh-button" type="submit">Apply filters</button>
    </form>

    <section class="egh-card" aria-label="User accounts">
        <div class="admin-table-wrap">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Verified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td><span class="admin-status-badge">{{ $user->getRoleNames()->join(', ') ?: 'No role' }}</span></td>
                            <td>{{ $user->email_verified_at ? 'Yes' : 'No' }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    <a class="admin-action-link" href="{{ route('admin.users.show', $user) }}">View</a>
                                    @can('users.manage')
                                        @if (auth()->user()->hasRole('super-admin') || ! $user->hasRole('super-admin'))
                                            <a class="admin-action-link" href="{{ route('admin.users.edit', $user) }}">Edit</a>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="admin-empty-state" colspan="5">No users match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($users->hasPages())
        <div class="admin-pagination">{{ $users->links() }}</div>
    @endif
@endsection
