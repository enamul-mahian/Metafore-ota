@extends('layouts.admin')

@section('title', 'Create Role')

@section('content')
    <x-admin.page-header
        title="Create Role"
        description="Define an authorization role and assign only the permissions it requires."
        icon="R"
        eyebrow="Access control"
    />

    <form method="POST" action="{{ route('admin.roles.store') }}" class="egh-card admin-form-card">
        @csrf

        <label class="admin-field">
            <span>Role name</span>
            <input name="name" value="{{ old('name') }}" required>
        </label>

        <fieldset class="admin-permission-fieldset">
            <legend>Permissions</legend>
            <div class="admin-permission-grid">
                @foreach ($permissions as $permission)
                    <label class="admin-check-card">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $permission->name }}"
                            @checked(in_array($permission->name, old('permissions', []), true))
                        >
                        <span>{{ $permission->name }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="admin-form-actions">
            <button class="egh-button" type="submit">Create Role</button>
            <a class="egh-button secondary" href="{{ route('admin.roles.index') }}">Cancel</a>
        </div>
    </form>
@endsection
