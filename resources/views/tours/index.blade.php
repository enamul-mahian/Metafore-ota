@extends('layouts.site')

@include('partials.site.route-seo', ['seoRoute' => 'tours.index'])

@section('og_image', $site->imageUrl('page.tours'))

@section('content')
<section class="page-hero page-hero--svc" style="background-image:url('{{ $site->imageUrl('page.tours') }}')">
    <div class="container page-hero__in">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>Tours &amp; Activities</span></nav>
        <h1>Explore Tours for Your Destination</h1>
        <p>City tours, day trips, cruises and activities. Search by destination, then send us the tour you like and we confirm availability for you.</p>
    </div>
</section>

<div class="container page">
    @if (! $service['available'])
        @include('partials.site.service-unavailable', [
            'icon' => 'palm',
            'badge' => 'Not Configured',
            'title' => 'Tour service is not configured',
            'text' => 'Tour search will be available after an approved provider adapter and its required server configuration are enabled.',
            'service' => 'tours',
        ])
    @else
        <section class="card svc-search" aria-labelledby="tour-search-title">
            <h2 class="card__title" id="tour-search-title"><span>Search available tours</span><svg class="ic"><use href="#i-palm"/></svg></h2>
            @include('tours._search-form')
        </section>
    @endif

    <section class="section">
        <div class="section__head"><div><h2>How Tour Booking Works</h2><p>Availability and price are confirmed with you before you pay.</p></div></div>
        <ol class="process process--4">
            <li><b>Search</b><p>Enter your destination, a preferred date and the number of travelers.</p></li>
            <li><b>Pick a tour</b><p>Compare the tours returned for your destination.</p></li>
            <li><b>Request booking</b><p>Send us the tour you like. Our team checks the date and price.</p></li>
            <li><b>Confirm & pay</b><p>You confirm the final price before the booking is made.</p></li>
        </ol>
    </section>

    <div class="cta-band">
        <div><h2>Want a tour plan made for you?</h2><p>Tell us your destination and interests and we will suggest tours and activities.</p></div>
        <a href="{{ route('enquiries.create', ['service' => 'tours']) }}" class="btn btn--white">Request a Tour Quote <svg class="ic"><use href="#i-arrow-r"/></svg></a>
    </div>
    <div style="height:8px"></div>
</div>
@endsection
