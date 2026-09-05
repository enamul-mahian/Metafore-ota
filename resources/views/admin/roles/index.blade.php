@extends('layouts.admin')

@section('title', 'Roles & Permissions')

@section('content')
    <x-admin.page-header
        title="Roles & Permissions"
        description="Review authorization roles, assigned users, and permission coverage."
        icon="R"
        eyebrow="Access control"
    >
        @can('roles.manage')
            <a class="egh-button" href="{{ route('admin.roles.create') }}">Create Role</a>
        @endcan
    </x-admin.page-header>

    <section class="egh-card" aria-label="Authorization roles">
        <div class="admin-table-wrap">
            <table class="admin-data-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Users</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td><strong>{{ $role->name }}</strong></td>
                            <td>{{ $role->users_count }}</td>
                            <td>{{ $role->permissions_count }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    <a class="admin-action-link" href="{{ route('admin.roles.show', $role) }}">View</a>
                                    @can('roles.manage')
                                        @if ($role->name !== 'super-admin')
                                            <a class="admin-action-link" href="{{ route('admin.roles.edit', $role) }}">Edit</a>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="admin-empty-state" colspan="4">No roles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
