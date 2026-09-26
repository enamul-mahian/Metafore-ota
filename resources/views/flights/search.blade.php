@extends('layouts.site')

@section('title', 'Search Flights')
@section('body_class', 'flights-body')
@section('og_image', $site->imageUrl('page.flights'))

@php
    /*
     * Prefill from the homepage search form (GET) or the sign-in return link.
     * Values are format-checked here; the search endpoint validates fully.
     */
    $prefill = \App\Services\Flight\FlightSearchPrefill::fromInput(request()->query());
    $autoSearch = \App\Services\Flight\FlightSearchPrefill::canAutoSearch($prefill);
    $tripType = $prefill['trip_type']
        ?? (isset($prefill['origin']) && ! isset($prefill['return_date']) ? 'one_way' : 'round_trip');
    $cabinClass = $prefill['cabin_class'] ?? 'economy';
@endphp

@push('head')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Flights', 'item' => route('flights.index')],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
@endpush

@section('content')

    <div class="fs" data-flight-page data-flight-stage="search">

        <section class="fs-hero" style="background-image:url('{{ $site->imageUrl('page.flights') }}')">
            <div class="container fs-hero__in">
                <nav class="crumbs" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <span>Flights</span>
                </nav>
                <h1>Search Flights</h1>
                <p>Compare domestic and international flights, then book securely with your Eagle Global Hub account.</p>
            </div>
        </section>

        <section class="fs-page">
            <div class="container">

                <form
                    method="POST"
                    action="{{ route('flights.search') }}"
                    class="fs-form"
                    data-flight-search-form
                    @if ($autoSearch) data-flight-auto-search @endif
                    aria-label="Flight search"
                >
                    @csrf

                    {{-- Search bar --}}
                    <div class="fs-bar">
                        <fieldset class="fs-trip">
                            <legend class="sr-only">Trip Type</legend>

                            <label class="radio">
                                <input type="radio" name="trip_type" value="round_trip" @checked($tripType === 'round_trip')>
                                <span></span>Round Trip
                            </label>

                            <label class="radio">
                                <input type="radio" name="trip_type" value="one_way" @checked($tripType === 'one_way')>
                                <span></span>One Way
                            </label>
                        </fieldset>

                        <div class="fs-grid">
                            <div class="fs-route">
                                <div class="fld">
                                    <label for="flight-origin">From</label>
                                    <div class="fld__box">
                                        <svg class="ic" aria-hidden="true"><use href="#i-plane"/></svg>
                                        <input
                                            id="flight-origin"
                                            type="text"
                                            name="origin"
                                            maxlength="3"
                                            minlength="3"
                                            pattern="[A-Za-z]{3}"
                                            placeholder="DAC"
                                            value="{{ $prefill['origin'] ?? '' }}"
                                            autocomplete="off"
                                            autocapitalize="characters"
                                            spellcheck="false"
                                            enterkeyhint="next"
                                            aria-describedby="flight-origin-help"
                                            required
                                            data-airport-code
                                        >
                                    </div>
                                    <small id="flight-origin-help" class="fs-help">3-letter airport code</small>
                                    <small class="flight-field-error" data-error-for="origin"></small>
                                </div>

                                <button type="button" class="swap fs-swap" data-flight-swap aria-label="Swap origin and destination">
                                    <svg class="ic"><use href="#i-swap"/></svg>
                                </button>

                                <div class="fld">
                                    <label for="flight-destination">To</label>
                                    <div class="fld__box">
                                        <svg class="ic" aria-hidden="true"><use href="#i-pin"/></svg>
                                        <input
                                            id="flight-destination"
                                            type="text"
                                            name="destination"
                                            maxlength="3"
                                            minlength="3"
                                            pattern="[A-Za-z]{3}"
                                            placeholder="DXB"
                                            value="{{ $prefill['destination'] ?? '' }}"
                                            autocomplete="off"
                                            autocapitalize="characters"
                                            spellcheck="false"
                                            enterkeyhint="next"
                                            aria-describedby="flight-destination-help"
                                            required
                                            data-airport-code
                                        >
                                    </div>
                                    <small id="flight-destination-help" class="fs-help">3-letter airport code</small>
                                    <small class="flight-field-error" data-error-for="destination"></small>
                                </div>
                            </div>

                            <div class="fld">
                                <label for="flight-departure">Departure</label>
                                <div class="fld__box">
                                    <svg class="ic" aria-hidden="true"><use href="#i-cal"/></svg>
                                    <input
                                        id="flight-departure"
                                        type="date"
                                        name="departure_date"
                                        min="{{ now()->toDateString() }}"
                                        value="{{ $prefill['departure_date'] ?? '' }}"
                                        required
                                        data-departure-date
                                    >
                                </div>
                                <small class="flight-field-error" data-error-for="departure_date"></small>
                            </div>

                            <div class="fld">
                                <label for="flight-return">Return</label>
                                <div class="fld__box">
                                    <svg class="ic" aria-hidden="true"><use href="#i-cal"/></svg>
                                    <input
                                        id="flight-return"
                                        type="date"
                                        name="return_date"
                                        min="{{ now()->addDay()->toDateString() }}"
                                        value="{{ $prefill['return_date'] ?? '' }}"
                                        required
                                        data-return-date
                                    >
                                </div>
                                <small class="flight-field-error" data-error-for="return_date"></small>
                            </div>

                            <div class="fld">
                                <label for="flight-cabin">Cabin</label>
                                <div class="fld__box">
                                    <svg class="ic" aria-hidden="true"><use href="#i-badge"/></svg>
                                    <select id="flight-cabin" name="cabin_class" required>
                                        <option value="economy" @selected($cabinClass === 'economy')>Economy</option>
                                        <option value="premium_economy" @selected($cabinClass === 'premium_economy')>Premium Economy</option>
                                        <option value="business" @selected($cabinClass === 'business')>Business</option>
                                        <option value="first" @selected($cabinClass === 'first')>First Class</option>
                                    </select>
                                </div>
                                <small class="flight-field-error" data-error-for="cabin_class"></small>
                            </div>

                            <details class="fs-pax" data-flight-pax>
                                <summary class="fld">
                                    <span class="fs-pax__label">Travellers</span>
                                    <span class="fld__box">
                                        <svg class="ic" aria-hidden="true"><use href="#i-users"/></svg>
                                        <span data-flight-pax-summary>1 Traveller</span>
                                        <svg class="ic fs-pax__chev" aria-hidden="true"><use href="#i-chev-d"/></svg>
                                    </span>
                                </summary>

                                <div class="fs-pax__pop">
                                    <p class="hint">Maximum 9 travellers per search.</p>

                                    <div class="fs-pax__row">
                                        <label for="flight-adults"><b>Adults</b><small>12+ years</small></label>
                                        <input id="flight-adults" class="input" type="number" inputmode="numeric" name="adults" min="1" max="9" value="{{ $prefill['adults'] ?? 1 }}" required>
                                        <small class="flight-field-error" data-error-for="adults"></small>
                                    </div>

                                    <div class="fs-pax__row">
                                        <label for="flight-children"><b>Children</b><small>2–11 years</small></label>
                                        <input id="flight-children" class="input" type="number" inputmode="numeric" name="children" min="0" max="8" value="{{ $prefill['children'] ?? 0 }}" required>
                                        <small class="flight-field-error" data-error-for="children"></small>
                                    </div>

                                    <div class="fs-pax__row">
                                        <label for="flight-infants"><b>Infants</b><small>Under 2 years</small></label>
                                        <input id="flight-infants" class="input" type="number" inputmode="numeric" name="infants" min="0" max="8" value="{{ $prefill['infants'] ?? 0 }}" required>
                                        <small class="flight-field-error" data-error-for="infants"></small>
                                    </div>

                                    <button type="button" class="btn btn--outline btn--sm btn--block" data-flight-pax-done>Done</button>
                                </div>
                            </details>

                            <button type="submit" class="btn btn--blue fs-submit" data-flight-submit>Search Flights</button>
                        </div>

                        <small class="flight-field-error fs-pax-error" data-error-for="passengers"></small>
                    </div>

                    {{-- Booking progress --}}
                    <div class="steps-card fs-steps" data-flight-steps-card hidden>
                        <ol class="steps" aria-label="Booking progress">
                            <li class="step" data-flight-step="search"><i>1</i><span>Search</span></li>
                            <li class="step" data-flight-step="select"><i>2</i><span>Select</span></li>
                            <li class="step" data-flight-step="travellers"><i>3</i><span>Travellers</span></li>
                            <li class="step" data-flight-step="review"><i>4</i><span>Review</span></li>
                            <li class="step" data-flight-step="payment"><i>5</i><span>Payment</span></li>
                            <li class="step" data-flight-step="confirmation"><i>6</i><span>Confirmation</span></li>
                        </ol>
                    </div>

                    <div class="fs-layout">
                        {{-- Filters (off-canvas on phones and tablets) --}}
                        <aside class="filters fs-filters" id="flightFilters" aria-label="Filter flights" data-flight-filters>
                            <div class="filters__head">
                                Filters
                                <button type="button" class="icon-btn filters__close" data-toggle="flightFilters" aria-label="Close filters"><svg class="ic"><use href="#i-x"/></svg></button>
                            </div>

                            <div class="filters__group">
                                <h4>Stops <button type="button" data-flight-filter-reset>Reset all</button></h4>
                                <div data-flight-filter-stops></div>
                            </div>

                            <div class="filters__group">
                                <h4>Airlines</h4>
                                <div data-flight-filter-airlines></div>
                            </div>

                            <div class="filters__group" data-flight-filter-price-group>
                                <h4>Price Range</h4>
                                <input type="range" class="range" data-flight-filter-price aria-label="Maximum total fare">
                                <div class="range-vals"><span data-flight-filter-price-min></span><span>up to <b data-flight-filter-price-value></b></span></div>
                            </div>

                            <div class="filters__group">
                                <h4>Departure Time</h4>
                                <div class="timeslots" data-flight-filter-slots>
                                    <button type="button" class="timeslot" data-slot="0" aria-pressed="false"><b>00 – 06</b>Early Morning</button>
                                    <button type="button" class="timeslot" data-slot="1" aria-pressed="false"><b>06 – 12</b>Morning</button>
                                    <button type="button" class="timeslot" data-slot="2" aria-pressed="false"><b>12 – 18</b>Afternoon</button>
                                    <button type="button" class="timeslot" data-slot="3" aria-pressed="false"><b>18 – 24</b>Evening</button>
                                </div>
                            </div>

                            <div class="filters__apply">
                                <button type="button" class="btn btn--blue btn--block" data-toggle="flightFilters">Show <span data-flight-shown-count>0</span> flights</button>
                            </div>
                        </aside>
                        <div class="backdrop" data-toggle="flightFilters" aria-hidden="true"></div>

                        <div class="fs-main">
                            <div class="results-head fs-results-head" data-flight-results-head>
                                <div>
                                    <h2 class="h2" data-flight-results-title>Available flights</h2>
                                    <p><span data-flight-shown-count>0</span> of <span data-flight-total-count>0</span> flights shown</p>
                                </div>
                                <button type="button" class="btn btn--outline btn--sm filters-toggle" data-toggle="flightFilters"><svg class="ic"><use href="#i-filter"/></svg>Filters</button>
                            </div>

                            <div class="sortbar fs-sortbar" data-flight-sortbar>
                                <div class="seg" role="group" aria-label="Sort flights">
                                    <button type="button" data-flight-sort="price" class="is-active" aria-pressed="true"><b>Cheapest</b><small data-flight-best="price"></small></button>
                                    <button type="button" data-flight-sort="duration" aria-pressed="false"><b>Fastest</b><small data-flight-best="duration"></small></button>
                                    <button type="button" data-flight-sort="departure" aria-pressed="false"><b>Earliest</b><small data-flight-best="departure"></small></button>
                                </div>
                            </div>

                            <div class="fs-change" data-flight-change hidden>
                                <button type="button" class="btn btn--outline btn--sm" data-flight-change-button>
                                    <svg class="ic"><use href="#i-chev-l"/></svg>Change flight
                                </button>
                            </div>

                            <div class="fs-body">
                                <div class="fs-flow">
                                    <div
                                        class="flight-status"
                                        data-flight-status
                                        role="status"
                                        aria-live="polite"
                                        hidden
                                    ></div>

                                    <div class="card empty fs-nomatch" data-flight-no-match hidden>
                                        <svg class="ic"><use href="#i-filter"/></svg>
                                        <p>No flights match these filters. <button type="button" class="link" data-flight-filter-reset>Reset all filters</button></p>
                                    </div>

                                    <div class="fs-intro" data-flight-intro>
                                        <div class="card fs-intro__card">
                                            <span class="svc__ic c-blue"><svg class="ic"><use href="#i-search"/></svg></span>
                                            <div>
                                                <h2 class="h3">Where would you like to go?</h2>
                                                <p class="muted small">
                                                    Enter your route, travel dates and passenger details above. Available flight
                                                    options appear here with carrier, schedule and total fare.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="grid-3 fs-how" aria-label="Flight search guidance">
                                            <article class="card">
                                                <span class="fs-how__n">01</span>
                                                <strong>Clear search details</strong>
                                                <p>Route, travel dates, cabin and passenger counts are checked before your search continues.</p>
                                            </article>
                                            <article class="card">
                                                <span class="fs-how__n">02</span>
                                                <strong>Easy fare comparison</strong>
                                                <p>Review carrier, itinerary timing and fare information before choosing a flight option.</p>
                                            </article>
                                            <article class="card">
                                                <span class="fs-how__n">03</span>
                                                <strong>Secure traveler review</strong>
                                                <p>Traveler details and booking review remain inside your signed-in account and are validated server-side.</p>
                                            </article>
                                        </div>

                                        <p class="fs-enquiry muted small">
                                            Prefer help from our team?
                                            @if (Route::has('enquiries.create'))
                                                <a class="link" href="{{ route('enquiries.create', ['service' => 'flights']) }}">Request a flight quote</a>
                                            @elseif (Route::has('contact'))
                                                <a class="link" href="{{ route('contact') }}">Request a flight quote</a>
                                            @endif
                                        </p>
                                    </div>

                                    <div
                                        class="flight-results"
                                        data-flight-results data-flight-select-url="{{ route('flights.offers.select') }}" data-flight-traveler-validation-url="{{ route('flights.travelers.validate') }}"
                                        data-flight-booking-draft-url="{{ route('flights.bookings.drafts.store') }}"
                                        data-flight-booking-draft-review-url="{{ route('flights.bookings.drafts.review') }}"
                                        data-flight-booking-confirmation-intent-url="{{ route('flights.bookings.confirmation-intents.store') }}"
                                        data-flight-order-execution-url="{{ route('flights.bookings.orders.execute') }}"
                                        data-flight-order-attempt-status-url-template="{{ route('flights.bookings.orders.attempts.show', ['attemptReference' => '__ATTEMPT_REFERENCE__']) }}"
                                        data-flight-order-reconciliation-url-template="{{ route('flights.bookings.orders.attempts.reconcile', ['attemptReference' => '__ATTEMPT_REFERENCE__']) }}"
                                        @feature('payments')
                                            data-flight-payment-readiness-url-template="{{ route('flights.bookings.orders.attempts.payment-readiness.show', ['attemptReference' => '__ATTEMPT_REFERENCE__']) }}"
                                            data-flight-payment-execution-url-template="{{ route('flights.bookings.orders.attempts.payments.store', ['attemptReference' => '__ATTEMPT_REFERENCE__']) }}"
                                            data-flight-payment-attempt-status-url-template="{{ route('flights.bookings.orders.payments.attempts.show', ['attemptReference' => '__ATTEMPT_REFERENCE__']) }}"
                                            data-flight-payment-reconciliation-url-template="{{ route('flights.bookings.orders.payments.attempts.reconcile', ['attemptReference' => '__ATTEMPT_REFERENCE__']) }}"
                                        @endfeature
                                        data-flight-order-confirmation-url-template="{{ route('flights.bookings.orders.attempts.confirmation.show', ['attemptReference' => '__ATTEMPT_REFERENCE__']) }}"
                                        @guest
                                            data-flight-sign-in-url="{{ route('flights.sign-in') }}"
                                        @endguest
                                        aria-live="polite"
                                        hidden
                                    ></div>
                                </div>

                                {{-- Sticky fare summary (filled from the server-resolved selection) --}}
                                <aside class="summary fs-summary" aria-label="Flight summary" data-flight-summary hidden>
                                    <div class="summary__head">Flight Summary</div>
                                    <div class="fsum">
                                        <span class="airline__logo fs-summary__mark" data-flight-summary-mark aria-hidden="true"></span>
                                        <div>
                                            <b data-flight-summary-airline></b>
                                            <div class="fsum__route" data-flight-summary-route></div>
                                            <small data-flight-summary-when></small>
                                        </div>
                                    </div>
                                    <p class="summary__label">Travellers</p>
                                    <p class="summary__row" data-flight-summary-travellers></p>
                                    <div class="summary__total"><span>Total fare</span><span data-flight-summary-total></span></div>
                                    <p class="hint">Total fare as returned by the airline supplier for all travellers. It is re-checked before any booking or payment.</p>
                                    <p class="secure"><svg class="ic"><use href="#i-lock"/></svg>Secure, server-validated checkout</p>
                                </aside>
                            </div>
                        </div>
                    </div>

                    {{-- Phone: sticky continue bar during checkout --}}
                    <div class="fs-sticky" data-flight-sticky hidden>
                        <div>
                            <small>Total fare</small>
                            <b data-flight-sticky-total></b>
                        </div>
                        <button type="button" class="btn btn--blue" data-flight-sticky-continue>Continue</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

@endsection

@push('scripts')
    @include('flights.partials.search-ui-script')
@endpush
