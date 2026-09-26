@extends('layouts.site')

@include('partials.site.route-seo', ['seoRoute' => 'hotels.index'])

@section('og_image', $site->imageUrl('page.hotels'))

@section('content')
<section class="page-hero page-hero--svc" style="background-image:url('{{ $site->imageUrl('page.hotels') }}')">
    <div class="container page-hero__in">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>Hotels</span></nav>
        <h1>Find a Stay for Your Journey</h1>
        <p>Search hotels, resorts and apartments by city and dates, then send us the one you like and we confirm rooms and rates for you.</p>
    </div>
</section>

<div class="container page">
    @if (! $service['available'])
        @include('partials.site.service-unavailable', [
            'icon' => 'hotel',
            'badge' => 'Not Configured',
            'title' => 'Hotel service is not configured',
            'text' => 'Hotel search will be available after an approved provider adapter and its required server configuration are enabled.',
            'service' => 'hotels',
        ])
    @else
        <section class="card svc-search" aria-labelledby="hotel-search-title">
            <h2 class="card__title" id="hotel-search-title"><span>Search available stays</span><svg class="ic"><use href="#i-hotel"/></svg></h2>
            @include('hotels._search-form')
        </section>
    @endif

    <section class="section">
        <div class="section__head"><div><h2>How Hotel Booking Works</h2><p>Every step is confirmed with you before anything is paid.</p></div></div>
        <ol class="process process--4">
            <li><b>Search</b><p>Enter a city, your dates and how many rooms and adults.</p></li>
            <li><b>Choose a stay</b><p>Compare the hotels returned for your search.</p></li>
            <li><b>Request booking</b><p>Send us the hotel you like. Our team checks rooms, rates and rules.</p></li>
            <li><b>Confirm & pay</b><p>You confirm the final price before the booking is made.</p></li>
        </ol>
    </section>

    <div class="cta-band">
        <div><h2>Prefer us to find the hotel?</h2><p>Tell us your city, dates and budget and we will suggest stays that fit.</p></div>
        <a href="{{ route('enquiries.create', ['service' => 'hotels']) }}" class="btn btn--white">Request a Hotel Quote <svg class="ic"><use href="#i-arrow-r"/></svg></a>
    </div>
    <div style="height:8px"></div>
</div>
@endsection
