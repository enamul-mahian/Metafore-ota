@extends('layouts.admin')

@section('title', 'Role Details')

@section('content')
    <x-admin.page-header
        :title="$role->name"
        :description="$role->users_count.' assigned user(s).'"
        icon="R"
        eyebrow="Role details"
    >
        @can('roles.manage')
            @if ($role->name !== 'super-admin')
                <a class="egh-button" href="{{ route('admin.roles.edit', $role) }}">Edit Role</a>
            @endif
        @endcan
    </x-admin.page-header>

    <section class="egh-card">
        <div class="admin-card-heading">
            <div>
                <span class="admin-page-eyebrow">Authorization</span>
                <h2>Assigned permissions</h2>
            </div>
            <span class="admin-status-badge">{{ $role->permissions->count() }} total</span>
        </div>

        <div class="admin-badge-list">
            @forelse ($role->permissions as $permission)
                <span class="admin-permission-badge">{{ $permission->name }}</span>
            @empty
                <p class="admin-empty-copy">No permissions assigned.</p>
            @endforelse
        </div>
    </section>

    @can('roles.manage')
        @if (! in_array($role->name, ['super-admin', 'admin', 'customer'], true) && $role->users_count === 0)
            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="admin-danger-zone" onsubmit="return confirm('Delete this role?')">
                @csrf
                @method('DELETE')
                <button class="egh-button danger" type="submit">Delete Role</button>
            </form>
        @endif
    @endcan
@endsection
