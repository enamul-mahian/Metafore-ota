@extends('layouts.site')

@section('title', 'Dashboard')
@section('body_class', 'dashboard-body')

@section('content')

    <main class="dashboard-container">

        <section class="dashboard-welcome">

            <div>
                <span class="dashboard-kicker">
                    WELCOME BACK
                </span>

                <h1>
                    Hello, {{ auth()->user()->name }}
                </h1>

                <p>
                    Search flights, review your account details and continue
                    your travel journey from one secure dashboard.
                </p>

                <div class="dashboard-welcome-actions">

                    @can('flights.search')
                        <a
                            href="{{ route('flights.index') }}"
                            class="site-button site-button-primary"
                        >
                            Search Flights
                        </a>
                    @endcan

                    <a
                        href="{{ route('account.overview') }}"
                        class="site-button site-button-secondary"
                    >
                        View Account
                    </a>

                </div>
            </div>

            <div class="dashboard-verified">
                <span aria-hidden="true">&#10003;</span>

                <div>
                    <strong>Email Verified</strong>

                    <small>
                        Your verified account can access protected travel pages.
                    </small>
                </div>
            </div>

        </section>

        <section class="dashboard-section">

            <div class="dashboard-section-heading">
                <div>
                    <span class="dashboard-kicker">
                        QUICK ACCESS
                    </span>

                    <h2>
                        Continue your journey
                    </h2>
                </div>
            </div>

            <div class="dashboard-quick-grid">

                @can('flights.search')
                    <a
                        href="{{ route('flights.index') }}"
                        class="dashboard-quick-card"
                    >
                        <span class="dashboard-quick-icon" aria-hidden="true">
                            &#9992;
                        </span>

                        <div>
                            <strong>Flight Search</strong>

                            <small>
                                Search available flight options by route,
                                date, cabin and travelers.
                            </small>
                        </div>

                        <b aria-hidden="true">&rarr;</b>
                    </a>
                @endcan

                <a
                    href="{{ route('account.overview') }}"
                    class="dashboard-quick-card"
                >
                    <span class="dashboard-quick-icon" aria-hidden="true">
                        A
                    </span>

                    <div>
                        <strong>Account Overview</strong>

                        <small>
                            Review your name, email, verification and
                            account timeline.
                        </small>
                    </div>

                    <b aria-hidden="true">&rarr;</b>
                </a>

            </div>

        </section>

        <section class="dashboard-section">

            <div class="dashboard-section-heading">
                <div>
                    <span class="dashboard-kicker">
                        TRAVEL SERVICES
                    </span>

                    <h2>
                        Services
                    </h2>
                </div>
            </div>

            <div class="dashboard-service-grid">

                @can('flights.search')
                    <a
                        href="{{ route('flights.index') }}"
                        class="dashboard-service-card dashboard-service-card-link"
                    >
                        <div class="service-icon" aria-hidden="true">
                            &#9992;
                        </div>

                        <h3>Flights</h3>

                        <p>
                            Search domestic and international flight options.
                        </p>

                        <span class="service-status service-status-live">
                            Available
                        </span>
                    </a>
                @else
                    <article class="dashboard-service-card">
                        <div class="service-icon" aria-hidden="true">
                            &#9992;
                        </div>

                        <h3>Flights</h3>

                        <p>
                            Flight access is not enabled for this account.
                        </p>

                        <span class="service-status">
                            Unavailable
                        </span>
                    </article>
                @endcan

                <article class="dashboard-service-card">
                    <div class="service-icon" aria-hidden="true">
                        H
                    </div>

                    <h3>Hotels</h3>

                    <p>
                        Hotel booking is not part of the active service yet.
                    </p>

                    <span class="service-status">
                        Coming Soon
                    </span>
                </article>

                <article class="dashboard-service-card">
                    <div class="service-icon" aria-hidden="true">
                        T
                    </div>

                    <h3>Tours</h3>

                    <p>
                        Tour booking is not part of the active service yet.
                    </p>

                    <span class="service-status">
                        Coming Soon
                    </span>
                </article>

                <article class="dashboard-service-card">
                    <div class="service-icon" aria-hidden="true">
                        V
                    </div>

                    <h3>Visa</h3>

                    <p>
                        Visa services are not part of the active service yet.
                    </p>

                    <span class="service-status">
                        Coming Soon
                    </span>
                </article>

            </div>

        </section>

        <section class="dashboard-account-card">

            <div class="dashboard-account-heading">
                <div>
                    <span class="dashboard-kicker">
                        YOUR ACCOUNT
                    </span>

                    <h2>
                        Account snapshot
                    </h2>
                </div>

                <a
                    href="{{ route('account.overview') }}"
                    class="dashboard-account-link"
                >
                    Open Account
                </a>
            </div>

            <div class="dashboard-account-grid">

                <div>
                    <span>Full Name</span>
                    <strong>{{ auth()->user()->name }}</strong>
                </div>

                <div>
                    <span>Email Address</span>
                    <strong>{{ auth()->user()->email }}</strong>
                </div>

                <div>
                    <span>Email Status</span>
                    <strong class="verified-text">
                        Verified
                    </strong>
                </div>

                <div>
                    <span>Member Since</span>

                    <strong>
                        {{ auth()->user()->created_at?->format('M Y') ?? '—' }}
                    </strong>
                </div>

            </div>

        </section>

    </main>

@endsection