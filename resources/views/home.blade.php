@extends('layouts.site')

@section('title', 'Flights & Travel')

@section(
    'meta_description',
    'Search and manage flight journeys with Eagle Global Hub LTD.'
)

@section('content')

    <main class="site-home">

        <section class="site-hero">
            <div class="site-hero-inner">

                <div class="site-hero-copy">
                    <span class="site-eyebrow">
                        FLIGHT-FIRST TRAVEL EXPERIENCE
                    </span>

                    <h1>
                        Plan your next journey with clarity.
                    </h1>

                    <p>
                        Search flight options, review itinerary details and
                        move through a structured booking journey from one
                        secure account.
                    </p>

                    <div class="site-hero-actions">

                        @auth
                            @can('flights.search')
                                <a
                                    href="{{ route('flights.index') }}"
                                    class="site-button site-button-primary site-button-large"
                                >
                                    Search Flights
                                </a>
                            @endcan

                            <a
                                href="{{ route('dashboard') }}"
                                class="site-button site-button-secondary site-button-large"
                            >
                                Open Dashboard
                            </a>
                        @else
                            <a
                                href="{{ route('register') }}"
                                class="site-button site-button-primary site-button-large"
                            >
                                Create Account
                            </a>

                            <a
                                href="{{ route('login') }}"
                                class="site-button site-button-secondary site-button-large"
                            >
                                Login to Search
                            </a>
                        @endauth

                    </div>

                    <div class="site-hero-points">
                        <span>Secure account access</span>
                        <span>Clear itinerary review</span>
                        <span>Booking-state visibility</span>
                    </div>
                </div>

                <div class="site-journey-card">

                    <span class="site-journey-kicker">
                        YOUR FLIGHT JOURNEY
                    </span>

                    <div class="site-journey-route">

                        <div>
                            <small>From</small>
                            <strong>DAC</strong>
                            <span>Dhaka</span>
                        </div>

                        <div
                            class="site-journey-line"
                            aria-hidden="true"
                        >
                            <span></span>
                            <b>&#9992;</b>
                            <span></span>
                        </div>

                        <div>
                            <small>To</small>
                            <strong>DXB</strong>
                            <span>Dubai</span>
                        </div>

                    </div>

                    <div class="site-journey-meta">

                        <div>
                            <small>Step 1</small>
                            <strong>Search</strong>
                        </div>

                        <div>
                            <small>Step 2</small>
                            <strong>Review</strong>
                        </div>

                        <div>
                            <small>Step 3</small>
                            <strong>Confirm</strong>
                        </div>

                    </div>

                    <p class="site-journey-note">
                        Route shown for presentation only. Actual availability
                        is returned by the configured flight data source.
                    </p>

                </div>

            </div>
        </section>

        <section class="site-section">

            <div class="site-section-heading">
                <span class="site-eyebrow">
                    A BETTER BOOKING FLOW
                </span>

                <h2>
                    Everything important stays visible.
                </h2>

                <p>
                    The booking experience is designed around clear decisions,
                    secure account access and transparent booking progress.
                </p>
            </div>

            <div class="site-feature-grid">

                <article class="site-feature-card">
                    <span class="site-feature-number">01</span>

                    <h3>Search confidently</h3>

                    <p>
                        Enter route, date, cabin and passenger details through
                        a focused flight-search experience.
                    </p>
                </article>

                <article class="site-feature-card">
                    <span class="site-feature-number">02</span>

                    <h3>Review before continuing</h3>

                    <p>
                        Keep itinerary, traveler and booking details visible as
                        you move through the booking flow.
                    </p>
                </article>

                <article class="site-feature-card">
                    <span class="site-feature-number">03</span>

                    <h3>Follow booking status</h3>

                    <p>
                        Order, payment and confirmation states remain part of
                        the controlled server-authoritative workflow.
                    </p>
                </article>

            </div>
        </section>

        <section class="site-section site-services-section">

            <div class="site-section-heading">
                <span class="site-eyebrow">
                    TRAVEL SERVICES
                </span>

                <h2>
                    Flights now. More services when ready.
                </h2>
            </div>

            <div class="site-service-list">

                <article
                    class="site-service-item site-service-item-active"
                >
                    <span>&#9992;</span>

                    <div>
                        <strong>Flights</strong>
                        <small>Current booking experience</small>
                    </div>

                    <b>Available</b>
                </article>

                <article class="site-service-item">
                    <span>H</span>

                    <div>
                        <strong>Hotels</strong>
                        <small>Not currently part of the active booking flow</small>
                    </div>

                    <b>Coming Soon</b>
                </article>

                <article class="site-service-item">
                    <span>T</span>

                    <div>
                        <strong>Tours</strong>
                        <small>Not currently part of the active booking flow</small>
                    </div>

                    <b>Coming Soon</b>
                </article>

                <article class="site-service-item">
                    <span>V</span>

                    <div>
                        <strong>Visa</strong>
                        <small>Not currently part of the active booking flow</small>
                    </div>

                    <b>Coming Soon</b>
                </article>

            </div>

        </section>

    </main>

@endsection