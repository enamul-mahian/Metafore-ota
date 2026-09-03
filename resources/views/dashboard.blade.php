@extends('layouts.site')

@section('title', 'Dashboard')
@section('body_class', 'dashboard-body')

@section('content')

<main class="dashboard-container">

        <section class="dashboard-welcome">
            <div>
                <span class="dashboard-kicker">
                    WELCOME TO Eagle Global Hub LTD
                </span>

                <h1>
                    Hello, {{ auth()->user()->name }} 👋
                </h1>

                <p>
                    Manage flight searches, booking progress and account details
                    from one secure place.
                </p>
            </div>

            <div class="dashboard-verified">
                <span>✓</span>

                <div>
                    <strong>Email Verified</strong>
                    <small>Your account is active and secured.</small>
                </div>
            </div>
        </section>

        <section class="dashboard-section">
            <div class="dashboard-section-heading">
                <div>
                    <span class="dashboard-kicker">TRAVEL SERVICES</span>
                    <h2>Explore Services</h2>
                </div>
            </div>

            <div class="dashboard-service-grid">

                @can('flights.search')
                    <a
                        href="{{ route('flights.index') }}"
                        class="dashboard-service-card dashboard-service-card-link"
                    >
                        <div class="service-icon">✈</div>

                        <h3>Flights</h3>

                        <p>
                            Search domestic and international flight options.
                        </p>

                        <span class="service-status service-status-live">
                            Search Flights
                        </span>
                    </a>
                @else
                    <article class="dashboard-service-card">
                        <div class="service-icon">✈</div>

                        <h3>Flights</h3>

                        <p>
                            Search and book domestic and international flights.
                        </p>

                        <span class="service-status">Coming Soon</span>
                    </article>
                @endcan

                <article class="dashboard-service-card">
                    <div class="service-icon">⌂</div>

                    <h3>Hotels</h3>

                    <p>
                        Discover hotels and accommodation for your journey.
                    </p>

                    <span class="service-status">Coming Soon</span>
                </article>

                <article class="dashboard-service-card">
                    <div class="service-icon">⌖</div>

                    <h3>Tours</h3>

                    <p>
                        Explore curated tour packages and travel experiences.
                    </p>

                    <span class="service-status">Coming Soon</span>
                </article>

                <article class="dashboard-service-card">
                    <div class="service-icon">✓</div>

                    <h3>Visa</h3>

                    <p>
                        Manage visa assistance and application services.
                    </p>

                    <span class="service-status">Coming Soon</span>
                </article>

            </div>
        </section>

        <section class="dashboard-account-card">

            <div>
                <span class="dashboard-kicker">YOUR ACCOUNT</span>

                <h2>Account Overview</h2>
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
                    <span>Account Type</span>
                    <strong>Customer</strong>
                </div>

            </div>

        </section>

    </main>
@endsection
