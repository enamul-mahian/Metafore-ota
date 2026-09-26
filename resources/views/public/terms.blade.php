@extends('layouts.site')

@section('content')
<section class="page-hero page-hero--light">
    <div class="container page-hero__in">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>Terms</span></nav>
        <h1>Website terms and booking notices</h1>
        <p>These customer-facing notices explain how the website presents flight search, booking status and supplier-dependent execution without replacing formal supplier or airline terms.</p>
    </div>
</section>

<div class="container">
    <section class="section info-stack">
        <article class="card">
            <h2>Flight Availability</h2>
            <p>Flight availability, fares and payment readiness are dependent on the configured data source. Fixture or sandbox data must not be treated as live airline inventory.</p>
        </article>
        <article class="card">
            <h2>Booking Confirmation</h2>
            <p>Customer booking confirmations show stored order and payment status where available. They are not airline-issued tickets unless a supplier-issued ticket document is explicitly provided.</p>
        </article>
        <article class="card">
            <h2>Quote Requests and Messages</h2>
            <p>Sending a quote request or a message does not create a booking or a payment. Prices, availability and conditions are confirmed with you before anything is booked.</p>
        </article>
        <article class="card">
            <h2>Visa Information</h2>
            <p>Visa requirement information is provided for guidance only. Visa decisions are made by the relevant embassy or authority, and approval or entry is never guaranteed.</p>
        </article>
        <article class="card">
            <h2>Privacy Notice</h2>
            <p>Protected booking pages require account access and are scoped to the signed-in customer. The public website does not display supplier credentials or unsafe supplier identifiers. Details sent through our forms are used to answer your request.</p>
        </article>
    </section>
    <div style="height:48px"></div>
</div>
@endsection
