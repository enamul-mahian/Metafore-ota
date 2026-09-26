@extends('layouts.site')

@section('title', 'My Profile')
@section('seo_title', 'My Profile | Eagle Global Hub')
@section('robots', 'noindex,nofollow')
@section('body_class', 'account-body')

@section('content')
    @php($user = auth()->user())

    <section class="page account-page">
        <div class="container">
            <nav class="crumbs" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span><a href="{{ route('account.overview') }}">My Account</a></span>
                <span>Profile</span>
            </nav>

            <div class="account-layout">
                @include('account.partials.side-menu', ['active' => 'profile'])

                <div class="stack">
                    <form
                        method="POST"
                        action="{{ route('user-profile-information.update') }}"
                        class="card account-form"
                        id="profile"
                        novalidate
                    >
                        @csrf
                        @method('PUT')

                        <div class="card__title">
                            <h1 class="h2">Profile Information</h1>
                            <span class="badge badge--success"><svg class="ic"><use href="#i-check"/></svg>Verified</span>
                        </div>

                        @if (session('status') === \Laravel\Fortify\Fortify::PROFILE_INFORMATION_UPDATED)
                            <p class="acc-notice acc-notice--success" role="status">
                                <svg class="ic"><use href="#i-check"/></svg>Profile information updated.
                            </p>
                        @endif

                        <div class="profile-head">
                            <span class="account-avatar-lg" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            <div>
                                <b class="h3">{{ $user->name }}</b>
                                <p class="muted small">Member since {{ $user->created_at?->format('F Y') ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="form-grid form-grid--2">
                            <div class="field">
                                <label for="profile-name">Full Name <em>*</em></label>
                                <input
                                    id="profile-name"
                                    class="input"
                                    name="name"
                                    type="text"
                                    value="{{ old('name', $user->name) }}"
                                    autocomplete="name"
                                    maxlength="255"
                                    required
                                    @error('name', 'updateProfileInformation')
                                        aria-invalid="true"
                                        aria-describedby="profile-name-error"
                                    @enderror
                                >
                                @error('name', 'updateProfileInformation')
                                    <span id="profile-name-error" class="field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="profile-email">Email Address <em>*</em></label>
                                <input
                                    id="profile-email"
                                    class="input"
                                    name="email"
                                    type="email"
                                    inputmode="email"
                                    value="{{ old('email', $user->email) }}"
                                    autocomplete="email"
                                    maxlength="255"
                                    required
                                    @error('email', 'updateProfileInformation')
                                        aria-invalid="true"
                                        aria-describedby="profile-email-error"
                                    @enderror
                                >
                                @error('email', 'updateProfileInformation')
                                    <span id="profile-email-error" class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <p class="hint account-form__hint">
                            Changing your email address requires you to verify the new address before
                            returning to verified-only pages.
                        </p>

                        <div class="account-form__actions">
                            <button type="submit" class="btn btn--blue">Save Changes</button>
                        </div>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('user-password.update') }}"
                        class="card account-form"
                        id="security"
                        novalidate
                    >
                        @csrf
                        @method('PUT')

                        <div class="card__title">
                            <h2 class="h2">Security</h2>
                            <svg class="ic"><use href="#i-lock"/></svg>
                        </div>

                        @if (session('status') === \Laravel\Fortify\Fortify::PASSWORD_UPDATED)
                            <p class="acc-notice acc-notice--success" role="status">
                                <svg class="ic"><use href="#i-check"/></svg>Password updated.
                            </p>
                        @endif

                        <input type="text" name="username" value="{{ $user->email }}" autocomplete="username" hidden>

                        <div class="form-grid">
                            <div class="field">
                                <label for="current-password">Current Password <em>*</em></label>
                                <input
                                    id="current-password"
                                    class="input"
                                    name="current_password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                    @error('current_password', 'updatePassword')
                                        aria-invalid="true"
                                        aria-describedby="current-password-error"
                                    @enderror
                                >
                                @error('current_password', 'updatePassword')
                                    <span id="current-password-error" class="field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="new-password">New Password <em>*</em></label>
                                <input
                                    id="new-password"
                                    class="input"
                                    name="password"
                                    type="password"
                                    autocomplete="new-password"
                                    required
                                    @error('password', 'updatePassword')
                                        aria-invalid="true"
                                        aria-describedby="new-password-error"
                                    @enderror
                                >
                                @error('password', 'updatePassword')
                                    <span id="new-password-error" class="field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="new-password-confirmation">Confirm New Password <em>*</em></label>
                                <input
                                    id="new-password-confirmation"
                                    class="input"
                                    name="password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    required
                                >
                            </div>
                        </div>

                        <div class="account-form__actions">
                            <button type="submit" class="btn btn--blue">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
