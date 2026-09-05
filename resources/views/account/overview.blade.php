@extends('layouts.site')

@section('title', 'Account Overview')
@section('body_class', 'account-body')

@section('content')

    <main class="account-container">

        <section class="account-hero">

            <div>
                <span class="account-kicker">
                    YOUR ACCOUNT
                </span>

                <h1>
                    Account Overview
                </h1>

                <p>
                    Review the account information currently stored for your
                    Eagle Global Hub LTD travel account.
                </p>
            </div>

            <div class="account-identity">
                <span class="account-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>

                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->email }}</small>
                </div>
            </div>

        </section>

        <section class="account-grid">

            <article class="account-panel">

                <div class="account-panel-heading">
                    <div>
                        <span class="account-kicker">
                            PROFILE
                        </span>

                        <h2>
                            Personal details
                        </h2>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('user-profile-information.update') }}"
                    class="account-profile-form"
                >
                    @csrf
                    @method('PUT')

                    @if (session('status') === \Laravel\Fortify\Fortify::PROFILE_INFORMATION_UPDATED)
                        <div class="account-form-success" role="status">
                            Profile information updated.
                        </div>
                    @endif

                    <div class="account-form-field">
                        <label for="account-name">Full Name</label>

                        <input
                            id="account-name"
                            name="name"
                            type="text"
                            value="{{ old('name', auth()->user()->name) }}"
                            autocomplete="name"
                            required
                            @error('name', 'updateProfileInformation')
                                aria-invalid="true"
                                aria-describedby="account-name-error"
                            @enderror
                        >

                        @error('name', 'updateProfileInformation')
                            <span id="account-name-error" class="account-form-error">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="account-form-field">
                        <label for="account-email">Email Address</label>

                        <input
                            id="account-email"
                            name="email"
                            type="email"
                            value="{{ old('email', auth()->user()->email) }}"
                            autocomplete="email"
                            required
                            @error('email', 'updateProfileInformation')
                                aria-invalid="true"
                                aria-describedby="account-email-error"
                            @enderror
                        >

                        @error('email', 'updateProfileInformation')
                            <span id="account-email-error" class="account-form-error">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <p class="account-form-note">
                        Changing your email address requires you to verify the
                        new address before returning to verified-only pages.
                    </p>

                    <button
                        type="submit"
                        class="site-button site-button-primary"
                    >
                        Save Profile
                    </button>
                </form>

            </article>

            <article class="account-panel">

                <div class="account-panel-heading">
                    <div>
                        <span class="account-kicker">
                            SECURITY
                        </span>

                        <h2>
                            Account status
                        </h2>
                    </div>
                </div>

                <dl class="account-detail-list">

                    <div>
                        <dt>Email Verification</dt>

                        <dd class="account-status-verified">
                            Verified
                        </dd>
                    </div>

                    <div>
                        <dt>Verified On</dt>

                        <dd>
                            {{
                                auth()->user()->email_verified_at
                                    ?->format('M j, Y')
                                ?? 'Verified'
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>Account Created</dt>

                        <dd>
                            {{
                                auth()->user()->created_at
                                    ?->format('M j, Y')
                                ?? '—'
                            }}
                        </dd>
                    </div>

                </dl>

            </article>

        </section>

        <section class="account-actions">

            <div>
                <span class="account-kicker">
                    TRAVEL
                </span>

                <h2>
                    Continue planning
                </h2>

                <p>
                    Return to your dashboard or continue to flight search.
                </p>
            </div>

            <div class="account-action-buttons">

                @feature('dashboard')
                    <a
                        href="{{ route('dashboard') }}"
                        class="site-button site-button-secondary"
                    >
                        Dashboard
                    </a>
                @endfeature

                @feature('flights')
                    @can('flights.search')
                        <a
                            href="{{ route('flights.index') }}"
                            class="site-button site-button-primary"
                        >
                            Search Flights
                        </a>
                    @endcan
                @endfeature

                @feature('bookings')
                    @can('flights.book')
                        <a
                            href="{{ route('bookings.index') }}"
                            class="site-button site-button-secondary"
                        >
                            My Bookings
                        </a>
                    @endcan
                @endfeature

            </div>

        </section>

    </main>

@endsection
