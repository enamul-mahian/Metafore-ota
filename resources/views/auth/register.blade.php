@extends('layouts.auth')

@section('title', 'Create Account | Eagle Global Hub LTD')
@section('hero-title', 'Explore The World With Eagle Global Hub LTD')
@section('hero-description', 'Create your account and start planning your next journey.')

@section('content')
    <div class="auth-heading">
        <span class="auth-kicker">GET STARTED</span>
        <h2>Create Account</h2>
        <p>Join us and start your journey.</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="auth-form">
        @csrf

        <div class="form-group">
            <label for="name">Full Name</label>

            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                autocomplete="name"
                placeholder="Your full name"
                required
                autofocus
            >
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                placeholder="you@example.com"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>

            <input
                id="password"
                type="password"
                name="password"
                autocomplete="new-password"
                placeholder="Create a password"
                required
            >
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>

            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                autocomplete="new-password"
                placeholder="Confirm your password"
                required
            >
        </div>

        <button type="submit" class="auth-button">
            Create Account
        </button>

        <p class="auth-footer-text">
            Already have an account?
            <a href="{{ route('login') }}">Login</a>
        </p>
    </form>
@endsection