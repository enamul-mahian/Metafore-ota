@extends('layouts.admin')

@section('title', 'Edit Role')

@section('content')
    <x-admin.page-header
        title="Edit Role"
        description="Review the role name and its assigned authorization permissions."
        icon="R"
        eyebrow="Access control"
    />

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="egh-card admin-form-card">
        @csrf
        @method('PATCH')

        <label class="admin-field">
            <span>Role name</span>
            <input
                name="name"
                value="{{ old('name', $role->name) }}"
                required
                @disabled($isSystemRole)
            >
        </label>
        @if ($isSystemRole)
            <input type="hidden" name="name" value="{{ $role->name }}">
        @endif

        <fieldset class="admin-permission-fieldset">
            <legend>Permissions</legend>
            <div class="admin-permission-grid">
                @foreach ($permissions as $permission)
                    <label class="admin-check-card">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $permission->name }}"
                            @checked(in_array($permission->name, old('permissions', $role->permissions->pluck('name')->all()), true))
                        >
                        <span>{{ $permission->name }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="admin-form-actions">
            <button class="egh-button" type="submit">Save Role</button>
            <a class="egh-button secondary" href="{{ route('admin.roles.show', $role) }}">Cancel</a>
        </div>
    </form>
@endsection
