@extends('layouts.site')

@include('partials.site.route-seo', ['seoRoute' => 'packages.index'])

@php
    $types = config('enquiries.package_types');
@endphp

@section('og_image', $site->imageUrl('page.packages'))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Holiday Packages', 'item' => route('packages.index')],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
@endpush

@section('content')
<section class="page-hero page-hero--svc" style="background-image:url('{{ $site->imageUrl('page.packages') }}')">
    <div class="container page-hero__in">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>Holiday Packages</span></nav>
        <h1>Holiday Packages Planned Around You</h1>
        <p>Flights, hotels, transfers and tours put together into one trip. Tell us where you want to go and we send you a package quote.</p>
        <div class="page-hero__actions">
            <a class="btn btn--blue btn--lg" href="{{ route('enquiries.create', ['service' => 'packages']) }}">Plan My Holiday <svg class="ic"><use href="#i-arrow-r"/></svg></a>
        </div>
    </div>
</section>

<div class="container">
    <section class="section">
        <div class="section__head"><div><h2>Choose Your Kind of Holiday</h2><p>Pick a package type and share your plans. Every package is quoted for your dates and group.</p></div></div>
        <div class="pkg-grid">
            @foreach ($types as $key => $type)
                <article class="pkg">
                    <span class="svc__ic {{ $type['color'] }}"><svg class="ic"><use href="#i-{{ $type['icon'] }}"/></svg></span>
                    <h3>{{ $type['label'] }} Packages</h3>
                    <p>{{ $type['text'] }}</p>
                    <a class="btn btn--outline btn--sm" aria-label="Request a quote for {{ mb_strtolower($type['label']) }} packages" href="{{ route('enquiries.create', ['service' => 'packages', 'package_type' => $type['value']]) }}">Request a quote <svg class="ic"><use href="#i-arrow-r"/></svg></a>
                </article>
            @endforeach
        </div>
    </section>

    <section class="section">
        <div class="section__head"><div><h2>How Package Planning Works</h2><p>No fixed prices on this page: every quote is built for your trip.</p></div></div>
        <ol class="process process--4">
            <li><b>Share your plans</b><p>Destination, month, number of nights, travellers and a rough budget.</p></li>
            <li><b>We build options</b><p>Our team combines flights, hotels, transfers and tours that fit your request.</p></li>
            <li><b>Review your quote</b><p>We send the itinerary and price. Ask for changes until it suits you.</p></li>
            <li><b>Confirm when ready</b><p>Nothing is booked or paid until you agree to the final quote.</p></li>
        </ol>
    </section>

    <section class="section">
        <div class="section__head"><div><h2>What a Package Can Include</h2></div></div>
        <div class="grid-4">
            <div class="feat card"><span class="svc__ic c-blue"><svg class="ic"><use href="#i-plane"/></svg></span><div><b>Return flights</b><p>Economy or premium cabins on the routes you prefer.</p></div></div>
            <div class="feat card"><span class="svc__ic c-green"><svg class="ic"><use href="#i-hotel"/></svg></span><div><b>Hotels & resorts</b><p>Stays chosen for your budget, location and group.</p></div></div>
            <div class="feat card"><span class="svc__ic c-amber"><svg class="ic"><use href="#i-car"/></svg></span><div><b>Transfers</b><p>Airport pick-up and drop-off, and transport between cities.</p></div></div>
            <div class="feat card"><span class="svc__ic c-indigo"><svg class="ic"><use href="#i-passport"/></svg></span><div><b>Tours & visa help</b><p>Sightseeing, activities and visa guidance where needed.</p></div></div>
        </div>
    </section>

    <div class="cta-band">
        <div><h2>Have a destination in mind?</h2><p>Send us your plans and our team will prepare a package quote for you.</p></div>
        <a href="{{ route('enquiries.create', ['service' => 'packages']) }}" class="btn btn--white">Request a Package Quote <svg class="ic"><use href="#i-arrow-r"/></svg></a>
    </div>

    <div class="news-band">
        <div><h2>Get holiday ideas by email</h2><p>Occasional travel updates and offers from our team.</p></div>
        @include('partials.site.newsletter-form', ['source' => 'packages'])
    </div>
    <div style="height:48px"></div>
</div>
@endsection
