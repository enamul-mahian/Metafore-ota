@extends('layouts.site')

@section('title', 'Contact & Support')
@section(
    'meta_description',
    'Customer support information for Eagle Global Hub LTD travel account and booking workflows.'
)
@section('body_class', 'public-page-body')

@section('content')

    <main class="public-page-container">

        <section class="public-page-hero">
            <span class="public-page-kicker">
                CONTACT & SUPPORT
            </span>

            <h1>
                Support for your travel account
            </h1>

            <p>
                Use your account pages to review booking status and customer
                booking documents. Direct support contact details can be added
                when Eagle Global Hub LTD configures official channels.
            </p>
        </section>

        <section class="public-page-grid">
            <article class="public-page-panel">
                <h2>Booking Status</h2>

                <p>
                    Signed-in customers can open My Bookings to review stored
                    order and payment status for their own flight booking
                    attempts.
                </p>
            </article>

            <article class="public-page-panel">
                <h2>Account Access</h2>

                <p>
                    If you cannot access protected pages, confirm that your
                    email address is verified and that you are signed in with
                    the account used for the booking.
                </p>
            </article>

            <article class="public-page-panel public-page-panel-muted">
                <h2>Official Contact Channels</h2>

                <p>
                    No public support email, phone number or office address is
                    configured in this website yet.
                </p>
            </article>
        </section>

    </main>

@endsection
