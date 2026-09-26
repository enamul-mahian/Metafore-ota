@extends('layouts.site')

@php
    $contact = $site->contact();
@endphp

@section('content')
<section class="page-hero page-hero--light">
    <div class="container page-hero__in">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>Support</span></nav>
        <h1>Support for your travel account</h1>
        <p>Use your account pages to review booking status and customer booking documents, or send our team a message.</p>
    </div>
</section>

<div class="container">
    <section class="section">
        <div class="info-grid">
            <article class="card info-card">
                <span class="svc__ic c-blue"><svg class="ic"><use href="#i-file"/></svg></span>
                <h2>Booking Status</h2>
                <p>Signed-in customers can open My Bookings to review stored order and payment status for their own flight booking attempts.</p>
            </article>
            <article class="card info-card">
                <span class="svc__ic c-green"><svg class="ic"><use href="#i-lock"/></svg></span>
                <h2>Account Access</h2>
                <p>If you cannot access protected pages, confirm that your email address is verified and that you are signed in with the account used for the booking.</p>
            </article>
            <article class="card info-card">
                <span class="svc__ic c-amber"><svg class="ic"><use href="#i-headset"/></svg></span>
                <h2>Official Contact Channels</h2>
                @if (isset($contact['phone']) || isset($contact['email']) || isset($contact['whatsapp']))
                    <ul class="stack" style="gap:8px;font-size:14px">
                        @isset($contact['phone'])<li><a class="link" href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}" data-track="contact">{{ $contact['phone'] }}</a></li>@endisset
                        @isset($contact['whatsapp'])<li><a class="link" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact['whatsapp']) }}" target="_blank" rel="noopener" data-track="contact">WhatsApp {{ $contact['whatsapp'] }}</a></li>@endisset
                        @isset($contact['email'])<li><a class="link" href="mailto:{{ $contact['email'] }}" data-track="contact">{{ $contact['email'] }}</a></li>@endisset
                        @isset($contact['hours'])<li class="muted">{{ $contact['hours'] }}</li>@endisset
                    </ul>
                @else
                    <p>No public support email, phone number or office address is configured in this website yet. You can still send us a message.</p>
                @endif
                @if (Route::has('contact'))
                    <a class="btn btn--outline btn--sm" href="{{ route('contact') }}">Send a message</a>
                @endif
            </article>
        </div>
    </section>

    @if (Route::has('enquiries.create'))
        <section class="section">
            <div class="section__head"><div><h2>Request a Quote</h2><p>Pick a service and tell us what you need.</p></div></div>
            <nav class="svc-links" aria-label="Request a quote" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr))">
                @foreach (collect(config('enquiries.services'))->except('other') as $key => $service)
                    <a href="{{ route('enquiries.create', ['service' => $key]) }}"><svg class="ic"><use href="#i-{{ $service['icon'] }}"/></svg>{{ $service['label'] }}</a>
                @endforeach
            </nav>
        </section>
    @endif
    <div style="height:48px"></div>
</div>
@endsection
