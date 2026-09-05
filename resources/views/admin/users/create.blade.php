@extends('layouts.admin')

@section('title', 'Create User')

@section('content')
    <h1>Create User</h1>

    <form
        method="POST"
        action="{{ route('admin.users.store') }}"
        class="egh-card"
    >
        @csrf

        <p>
            <label>
                Name
                <br>
                <input
                    name="name"
                    value="{{ old('name') }}"
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
                    value="{{ old('email') }}"
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
                            @selected(old('role') === $role->name)
                        >
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </label>
        </p>

        <p>
            <label>
                Password
                <br>
                <input
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    required
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
                    required
                >
            </label>
        </p>

        <button class="egh-button" type="submit">Create User</button>

        <a
            class="egh-button secondary"
            href="{{ route('admin.users.index') }}"
        >Cancel</a>
    </form>
@endsection
