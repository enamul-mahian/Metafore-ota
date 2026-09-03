@extends('layouts.auth')

@section('title', 'Login | Eagle Global Hub LTD')
@section('hero-title', 'Welcome Back!')
@section('hero-description', 'Login to continue your travel experience.')

@section('content')
    <div class="auth-heading">
        <span class="auth-kicker">WELCOME BACK</span>
        <h2>Login</h2>
        <p>Sign in to continue to your Eagle Global Hub LTD account.</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="auth-form">
        @csrf

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
                autofocus
            >
        </div>

        <div class="form-group">
            <div class="form-label-row">
                <label for="password">Password</label>

                <a href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            </div>

            <input
                id="password"
                type="password"
                name="password"
                autocomplete="current-password"
                placeholder="Enter your password"
                required
            >
        </div>

        <label class="checkbox-row">
            <input
                type="checkbox"
                name="remember"
                value="1"
                @checked(old('remember'))
            >

            <span>Remember me</span>
        </label>

        <button type="submit" class="auth-button">
            Login
        </button>

        <p class="auth-footer-text">
            Don't have an account?
            <a href="{{ route('register') }}">Create account</a>
        </p>
    </form>
@endsection