@extends('layouts.auth')

@section('title', 'Forgot Password | MetaFore OTA')

@section('hero-title', 'Forgot Your Password?')

@section(
    'hero-description',
    'No problem. Enter your email address and we will send you a password reset link.'
)

@section('content')
    <div class="auth-heading">
        <span class="auth-kicker">ACCOUNT RECOVERY</span>

        <h2>Forgot Password</h2>

        <p>
            Enter the email address associated with your MetaFore OTA account.
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('password.email') }}"
        class="auth-form"
    >
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

        <button type="submit" class="auth-button">
            Send Password Reset Link
        </button>

        <p class="auth-footer-text">
            Remember your password?

            <a href="{{ route('login') }}">
                Back to Login
            </a>
        </p>
    </form>
@endsection