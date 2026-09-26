@extends('layouts.site')

@section('og_image', $site->imageUrl('page.about'))

@section('content')
<section class="page-hero" style="background-image:url('{{ $site->imageUrl('page.about') }}')">
    <div class="container page-hero__in">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>About</span></nav>
        <h1>Eagle Global Hub LTD</h1>
        <p>A travel and visa agency helping people travel, work and study abroad, with clear search, careful review and visible booking progress.</p>
    </div>
</section>

<div class="container">
    <section class="section">
        <div class="section__head"><div><h2>How We Work</h2><p>Straightforward information about what this website does today.</p></div></div>
        <div class="info-grid">
            <article class="card info-card">
                <span class="svc__ic c-blue"><svg class="ic"><use href="#i-plane"/></svg></span>
                <h2>What We Support</h2>
                <p>The website supports flight search, traveler review, order status, payment status and customer booking confirmation pages where stored data is available. For other services you can send our team a request.</p>
            </article>
            <article class="card info-card">
                <span class="svc__ic c-green"><svg class="ic"><use href="#i-shield"/></svg></span>
                <h2>How We Present Travel Data</h2>
                <p>Availability and execution depend on the configured flight data source. Development and fixture results are labelled separately from live supplier inventory.</p>
            </article>
            <article class="card info-card">
                <span class="svc__ic c-amber"><svg class="ic"><use href="#i-grid"/></svg></span>
                <h2>Additional Services</h2>
                <p>Hotels, tours and visa discovery is shown as available only when its provider integration is configured. Transactional booking or application access is not implied.</p>
            </article>
        </div>
    </section>

    <div class="cta-band">
        <div><h2>Planning a trip?</h2><p>Tell us what you need and our team will get back to you with options.</p></div>
        @if (Route::has('contact'))
            <a href="{{ route('contact') }}" class="btn btn--white">Contact Us <svg class="ic"><use href="#i-arrow-r"/></svg></a>
        @endif
    </div>
    <div style="height:48px"></div>
</div>
@endsection
