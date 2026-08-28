@extends('layouts.auth')

@section('title', 'Reset Password | MetaFore OTA')

@section('hero-title', 'Create A New Password')

@section(
    'hero-description',
    'Choose a secure new password to regain access to your MetaFore OTA account.'
)

@section('content')
    <div class="auth-heading">
        <span class="auth-kicker">SECURE YOUR ACCOUNT</span>

        <h2>Reset Password</h2>

        <p>
            Enter your email address and choose a new password.
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('password.update') }}"
        class="auth-form"
    >
        @csrf

        <input
            type="hidden"
            name="token"
            value="{{ $request->route('token') }}"
        >

        <div class="form-group">
            <label for="email">Email Address</label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
                autocomplete="email"
                placeholder="you@example.com"
                required
                autofocus
            >
        </div>

        <div class="form-group">
            <label for="password">New Password</label>

            <input
                id="password"
                type="password"
                name="password"
                autocomplete="new-password"
                placeholder="Enter new password"
                required
            >
        </div>

        <div class="form-group">
            <label for="password_confirmation">
                Confirm New Password
            </label>

            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                autocomplete="new-password"
                placeholder="Confirm new password"
                required
            >
        </div>

        <button type="submit" class="auth-button">
            Reset Password
        </button>

        <p class="auth-footer-text">
            Remember your password?

            <a href="{{ route('login') }}">
                Back to Login
            </a>
        </p>
    </form>
@endsection