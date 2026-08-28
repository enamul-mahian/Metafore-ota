@extends('layouts.auth')

@section('title', 'Verify Email | MetaFore OTA')

@section('hero-title', 'Verify Your Email')

@section(
    'hero-description',
    'Confirm your email address to secure your account and continue your MetaFore OTA journey.'
)

@section('content')
    <div class="auth-heading">
        <span class="auth-kicker">EMAIL VERIFICATION</span>

        <h2>Check Your Inbox</h2>

        <p>
            We sent a verification link to your registered email address.
            Please verify your email before continuing.
        </p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="auth-alert auth-alert-success">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <div class="auth-form">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="auth-button">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="auth-secondary-button">
                Logout
            </button>
        </form>
    </div>
@endsection