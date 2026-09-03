@extends('layouts.site')

@section('title', 'Flights & Travel')

@section(
    'meta_description',
    'Search flights and manage your travel bookings with Eagle Global Hub LTD.'
)

@section('content')

    @php
        $flightSearchAction = route('login');
        $flightSearchMethod = 'GET';

        if (auth()->check()) {
            if (auth()->user()->can('flights.search')) {
                $flightSearchAction = route('flights.search');
                $flightSearchMethod = 'POST';
            } else {
                $flightSearchAction = route('dashboard');
            }
        }
    @endphp

    <main class="site-home site-ota-home">

        <section class="ota-hero">
            <div class="ota-hero-inner">

                <div class="ota-hero-copy">
                    <span class="site-eyebrow">
                        FLIGHTS FROM EAGLE GLOBAL HUB LTD
                    </span>

                    <h1>
                        Discover the World<br>
                        Your Journey Starts Here
                    </h1>

                    <p>
                        Search available journeys, compare fare details and
                        manage your booking from one secure account.
                    </p>
                </div>

                <form
                    method="{{ $flightSearchMethod }}"
                    action="{{ $flightSearchAction }}"
                    class="ota-search-card"
                    aria-label="Flight search"
                >
                    @if ($flightSearchMethod === 'POST')
                        @csrf
                    @endif

                    <input type="hidden" name="children" value="0">
                    <input type="hidden" name="infants" value="0">

                    <fieldset class="ota-trip-tabs">
                        <legend>Trip type</legend>

                        <label>
                            <input
                                type="radio"
                                name="trip_type"
                                value="round_trip"
                                checked
                            >
                            <span>Round Trip</span>
                        </label>

                        <label>
                            <input
                                type="radio"
                                name="trip_type"
                                value="one_way"
                            >
                            <span>One Way</span>
                        </label>
                    </fieldset>

                    <div class="ota-search-grid">
                        <label>
                            <span>From</span>
                            <input
                                type="text"
                                name="origin"
                                maxlength="3"
                                minlength="3"
                                pattern="[A-Za-z]{3}"
                                placeholder="Airport code"
                                autocomplete="off"
                                required
                            >
                        </label>

                        <label>
                            <span>To</span>
                            <input
                                type="text"
                                name="destination"
                                maxlength="3"
                                minlength="3"
                                pattern="[A-Za-z]{3}"
                                placeholder="Airport code"
                                autocomplete="off"
                                required
                            >
                        </label>

                        <label>
                            <span>Depart</span>
                            <input
                                type="date"
                                name="departure_date"
                                min="{{ now()->toDateString() }}"
                                required
                            >
                        </label>

                        <label>
                            <span>Return</span>
                            <input
                                type="date"
                                name="return_date"
                                min="{{ now()->addDay()->toDateString() }}"
                                required
                            >
                        </label>

                        <label>
                            <span>Travelers</span>
                            <select name="adults">
                                <option value="1">1 Traveler</option>
                                <option value="2">2 Travelers</option>
                                <option value="3">3 Travelers</option>
                                <option value="4">4 Travelers</option>
                                <option value="5">5 Travelers</option>
                                <option value="6">6 Travelers</option>
                            </select>
                        </label>

                        <label>
                            <span>Cabin</span>
                            <select name="cabin_class">
                                <option value="economy">Economy</option>
                                <option value="premium_economy">
                                    Premium Economy
                                </option>
                                <option value="business">Business</option>
                                <option value="first">First Class</option>
                            </select>
                        </label>

                        <button type="submit">
                            Search Flights
                        </button>
                    </div>
                </form>

            </div>
        </section>

        <section class="ota-trust" aria-label="Booking benefits">
            <article>
                <span class="ota-trust-icon" aria-hidden="true">&#10003;</span>
                <div>
                    <strong>Secure &amp; Reliable</strong>
                    <small>Protected account and booking steps</small>
                </div>
            </article>

            <article>
                <span class="ota-trust-icon" aria-hidden="true">&#8635;</span>
                <div>
                    <strong>Booking Updates</strong>
                    <small>Review saved order and payment status</small>
                </div>
            </article>

            <article>
                <span class="ota-trust-icon" aria-hidden="true">&#36;</span>
                <div>
                    <strong>Payment Checks</strong>
                    <small>Status is reconciled before confirmation</small>
                </div>
            </article>

            <article>
                <span class="ota-trust-icon" aria-hidden="true">?</span>
                <div>
                    <strong>Account Assistance</strong>
                    <small>Current options are listed on Support</small>
                </div>
            </article>
        </section>

        <section class="ota-services">
            <div>
                <span class="site-eyebrow">
                    PLAN YOUR JOURNEY
                </span>

                <h2>
                    Travel services
                </h2>
            </div>

            <div class="ota-service-list">
                <article class="is-live">
                    <strong>Flights</strong>
                    <span>Available</span>
                </article>

                <article>
                    <strong>Hotels</strong>
                    <span>Coming Soon</span>
                </article>

                <article>
                    <strong>Tours</strong>
                    <span>Coming Soon</span>
                </article>

                <article>
                    <strong>Visa</strong>
                    <span>Coming Soon</span>
                </article>
            </div>
        </section>

    </main>

@endsection
