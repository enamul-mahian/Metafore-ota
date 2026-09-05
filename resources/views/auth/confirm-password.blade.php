@extends('layouts.auth')

@section('title', 'Confirm Password | Eagle Global Hub LTD')
@section('hero-title', 'Confirm Your Password')
@section('hero-description', 'Re-enter your password before continuing to a protected account action.')

@section('content')
    <div class="auth-heading">
        <span class="auth-kicker">SECURITY CHECK</span>
        <h2>Confirm Password</h2>
        <p>
            For your security, please confirm your password before continuing.
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('password.confirm.store') }}"
        class="auth-form"
    >
        @csrf

        <div class="form-group">
            <label for="password">Password</label>

            <input
                id="password"
                type="password"
                name="password"
                autocomplete="current-password"
                placeholder="Enter your password"
                required
                autofocus
            >
        </div>

        <button type="submit" class="auth-button">
            Confirm Password
        </button>

        <p class="auth-footer-text">
            <a href="{{ route('dashboard') }}">
                Return to dashboard
            </a>
        </p>
    </form>
@endsection
