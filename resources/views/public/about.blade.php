@extends('layouts.site')

@section('title', 'About')
@section(
    'meta_description',
    'Learn about the Eagle Global Hub LTD flight-first travel booking experience.'
)
@section('body_class', 'public-page-body')

@section('content')

    <main class="public-page-container">

        <section class="public-page-hero">
            <span class="public-page-kicker">
                ABOUT
            </span>

            <h1>
                Eagle Global Hub LTD
            </h1>

            <p>
                Eagle Global Hub LTD provides a flight-first travel experience
                built around clear search, careful review, secure account
                access and visible booking progress.
            </p>
        </section>

        <section class="public-page-grid">
            <article class="public-page-panel">
                <h2>What We Support</h2>

                <p>
                    The current website focuses on flight search, traveler
                    review, order status, payment status and customer booking
                    confirmation pages where stored data is available.
                </p>
            </article>

            <article class="public-page-panel">
                <h2>How We Present Travel Data</h2>

                <p>
                    Availability and execution depend on the configured flight
                    data source. Development and fixture results are labelled
                    separately from live supplier inventory.
                </p>
            </article>

            <article class="public-page-panel">
                <h2>Future Services</h2>

                <p>
                    Hotels, tours and visa services are marked Coming Soon and
                    are not part of the active booking flow.
                </p>
            </article>
        </section>

    </main>

@endsection
