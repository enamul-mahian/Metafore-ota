<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Search Flights | MetaFore OTA</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="dashboard-body">

    <header class="dashboard-header">
        <a href="{{ route('dashboard') }}" class="dashboard-logo">
            <span class="dashboard-logo-icon">✈</span>
            <span>MetaFore OTA</span>
        </a>

        <div class="dashboard-user-area">
            <a href="{{ route('dashboard') }}" class="flight-dashboard-link">
                Dashboard
            </a>

            <div class="dashboard-user">
                <div class="dashboard-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>

                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>{{ auth()->user()->email }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="dashboard-logout">
                    Logout
                </button>
            </form>
        </div>
    </header>

    <main class="flight-container">

        <section class="flight-hero">
            <div>
                <span class="flight-kicker">
                    FLIGHT SEARCH
                </span>

                <h1>Where would you like to go?</h1>

                <p>
                    Search domestic and international flight options with
                    secure passenger and itinerary validation.
                </p>
            </div>

            <div class="flight-hero-badge">
                <span>✈</span>

                <div>
                    <strong>MetaFore Flights</strong>
                    <small>Fast, simple and secure search.</small>
                </div>
            </div>
        </section>

        <section class="flight-search-card">
            <div class="flight-card-heading">
                <div>
                    <span class="flight-kicker">PLAN YOUR JOURNEY</span>
                    <h2>Search Flights</h2>
                </div>

                <p>
                    Enter your route, travel dates and passenger details.
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('flights.search') }}"
                class="flight-search-form"
                data-flight-search-form
            >
                @csrf

                <fieldset class="flight-trip-type">
                    <legend>Trip Type</legend>

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

                <div class="flight-route-grid">
                    <div class="flight-form-field">
                        <label for="flight-origin">From</label>

                        <input
                            id="flight-origin"
                            type="text"
                            name="origin"
                            maxlength="3"
                            minlength="3"
                            pattern="[A-Za-z]{3}"
                            placeholder="DAC"
                            autocomplete="off"
                            required
                            data-airport-code
                        >

                        <small class="flight-field-help">
                            3-letter airport code
                        </small>

                        <small
                            class="flight-field-error"
                            data-error-for="origin"
                        ></small>
                    </div>

                    <div class="flight-route-arrow" aria-hidden="true">
                        ⇄
                    </div>

                    <div class="flight-form-field">
                        <label for="flight-destination">To</label>

                        <input
                            id="flight-destination"
                            type="text"
                            name="destination"
                            maxlength="3"
                            minlength="3"
                            pattern="[A-Za-z]{3}"
                            placeholder="CXB"
                            autocomplete="off"
                            required
                            data-airport-code
                        >

                        <small class="flight-field-help">
                            3-letter airport code
                        </small>

                        <small
                            class="flight-field-error"
                            data-error-for="destination"
                        ></small>
                    </div>
                </div>

                <div class="flight-form-grid">
                    <div class="flight-form-field">
                        <label for="flight-departure">
                            Departure
                        </label>

                        <input
                            id="flight-departure"
                            type="date"
                            name="departure_date"
                            min="{{ now()->toDateString() }}"
                            required
                            data-departure-date
                        >

                        <small
                            class="flight-field-error"
                            data-error-for="departure_date"
                        ></small>
                    </div>

                    <div class="flight-form-field">
                        <label for="flight-return">
                            Return
                        </label>

                        <input
                            id="flight-return"
                            type="date"
                            name="return_date"
                            min="{{ now()->addDay()->toDateString() }}"
                            required
                            data-return-date
                        >

                        <small
                            class="flight-field-error"
                            data-error-for="return_date"
                        ></small>
                    </div>

                    <div class="flight-form-field">
                        <label for="flight-cabin">
                            Cabin
                        </label>

                        <select
                            id="flight-cabin"
                            name="cabin_class"
                            required
                        >
                            <option value="economy">
                                Economy
                            </option>

                            <option value="premium_economy">
                                Premium Economy
                            </option>

                            <option value="business">
                                Business
                            </option>

                            <option value="first">
                                First Class
                            </option>
                        </select>

                        <small
                            class="flight-field-error"
                            data-error-for="cabin_class"
                        ></small>
                    </div>
                </div>

                <div class="flight-passenger-section">
                    <div class="flight-passenger-heading">
                        <div>
                            <strong>Passengers</strong>
                            <span>Maximum 9 travellers per search.</span>
                        </div>
                    </div>

                    <div class="flight-passenger-grid">
                        <div class="flight-form-field">
                            <label for="flight-adults">
                                Adults
                            </label>

                            <input
                                id="flight-adults"
                                type="number"
                                name="adults"
                                min="1"
                                max="9"
                                value="1"
                                required
                            >

                            <small
                                class="flight-field-error"
                                data-error-for="adults"
                            ></small>
                        </div>

                        <div class="flight-form-field">
                            <label for="flight-children">
                                Children
                            </label>

                            <input
                                id="flight-children"
                                type="number"
                                name="children"
                                min="0"
                                max="8"
                                value="0"
                                required
                            >

                            <small
                                class="flight-field-error"
                                data-error-for="children"
                            ></small>
                        </div>

                        <div class="flight-form-field">
                            <label for="flight-infants">
                                Infants
                            </label>

                            <input
                                id="flight-infants"
                                type="number"
                                name="infants"
                                min="0"
                                max="8"
                                value="0"
                                required
                            >

                            <small
                                class="flight-field-error"
                                data-error-for="infants"
                            ></small>
                        </div>
                    </div>

                    <small
                        class="flight-field-error"
                        data-error-for="passengers"
                    ></small>
                </div>

                <div
                    class="flight-status"
                    data-flight-status
                    role="status"
                    aria-live="polite"
                    hidden
                ></div>

                <div
                    class="flight-results"
                    data-flight-results
                    aria-live="polite"
                    hidden
                ></div>

                <div class="flight-form-actions">
                    <a
                        href="{{ route('dashboard') }}"
                        class="flight-secondary-button"
                    >
                        Back to Dashboard
                    </a>

                    <button
                        type="submit"
                        class="flight-search-button"
                        data-flight-submit
                    >
                        Search Flights
                    </button>
                </div>
            </form>
        </section>

        <section class="flight-info-grid">
            <article>
                <span>01</span>
                <div>
                    <strong>Validated Search</strong>
                    <p>
                        Airport, passenger and travel-date rules are
                        validated before supplier search.
                    </p>
                </div>
            </article>

            <article>
                <span>02</span>
                <div>
                    <strong>Secure Access</strong>
                    <p>
                        Flight Search is available only to authenticated,
                        verified and authorized users.
                    </p>
                </div>
            </article>

            <article>
                <span>03</span>
                <div>
                    <strong>Graceful Availability</strong>
                    <p>
                        Supplier outages or missing configuration are shown
                        as clear customer-friendly messages.
                    </p>
                </div>
            </article>
        </section>

    </main>

</body>
</html>
