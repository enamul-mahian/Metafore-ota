@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
    <h1>Edit User</h1>

    <form
        method="POST"
        action="{{ route('admin.users.update', $user) }}"
        class="egh-card"
    >
        @csrf
        @method('PATCH')

        <p>
            <label>
                Name
                <br>
                <input
                    name="name"
                    value="{{ old('name', $user->name) }}"
                    autocomplete="name"
                    required
                >
            </label>
        </p>

        <p>
            <label>
                Email
                <br>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $user->email) }}"
                    autocomplete="email"
                    required
                >
            </label>
        </p>

        <p>
            <label>
                Role
                <br>
                <select name="role" required>
                    @foreach ($roles as $role)
                        <option
                            value="{{ $role->name }}"
                            @selected(
                                old(
                                    'role',
                                    $user->getRoleNames()->first()
                                ) === $role->name
                            )
                        >
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </label>
        </p>

        <p>
            <label>
                New Password (optional)
                <br>
                <input
                    type="password"
                    name="password"
                    autocomplete="new-password"
                >
            </label>
        </p>

        <p>
            <label>
                Confirm Password
                <br>
                <input
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                >
            </label>
        </p>

        <button class="egh-button" type="submit">Save Changes</button>

        <a
            class="egh-button secondary"
            href="{{ route('admin.users.show', $user) }}"
        >Cancel</a>
    </form>
@endsection
