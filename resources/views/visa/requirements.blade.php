@extends('layouts.site')

@php
    $countryName = fn (string $iso3): string => $countryNames[$iso3] ?? $iso3;
    $hasInformation = ! (
        $information['summary'] === '' &&
        $information['requirements'] === [] &&
        $information['documents'] === []
    );
    $assistanceUrl = route('enquiries.create', [
        'service' => 'visa',
        'nationality' => $countryName($criteria['nationality']),
        'destination' => $countryName($criteria['destination_country']),
        'travel_date' => $criteria['departure_date'],
    ]);
@endphp

@section('seo_title', 'Visa Requirements for '.$countryName($criteria['destination_country']).' | Eagle Global Hub')
@section('robots', 'noindex,follow')

@section('content')
<section class="page-hero page-hero--light">
    <div class="container page-hero__in">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span><a href="{{ route('visa.index') }}">Visa</a></span><span>{{ $criteria['destination_country'] }}</span></nav>
        <h1>{{ $countryName($criteria['destination_country']) }} <span class="muted" style="font-size:.6em">({{ $criteria['destination_country'] }})</span></h1>
        <div class="req-crit">
            <span><svg class="ic"><use href="#i-passport"/></svg>Passport: {{ $criteria['nationality'] }}</span>
            <span><svg class="ic"><use href="#i-plane"/></svg>{{ $criteria['origin_country'] }} to {{ $criteria['destination_country'] }}</span>
            <span><svg class="ic"><use href="#i-cal"/></svg>Departure: {{ $criteria['departure_date'] }} {{ $criteria['departure_time'] }}</span>
            <span><svg class="ic"><use href="#i-clock"/></svg>Arrival: {{ $criteria['arrival_date'] }} {{ $criteria['arrival_time'] }}</span>
        </div>
    </div>
</section>

<div class="container page">
    @if (! $hasInformation)
        <section class="card empty" role="status">
            <svg class="ic"><use href="#i-passport"/></svg>
            <h2 class="h3">No visa information was returned</h2>
            <p>The configured source returned no visa requirements for this trip. Eligibility, documents and approval have not been assumed. Do not submit an application based on missing information.</p>
            <div class="row" style="justify-content:center;margin-top:14px">
                <a href="{{ route('visa.index') }}" class="btn btn--outline">Check another trip</a>
                <a href="{{ $assistanceUrl }}" class="btn btn--blue">Ask a visa specialist</a>
            </div>
        </section>
    @else
        <div class="stack">
            @if ($information['summary'] !== '')
                <section class="card req-card" role="status">
                    <h2 class="card__title">Trip summary</h2>
                    <p>{{ $information['summary'] }}</p>
                </section>
            @endif

            <div class="req">
                <article class="card req-card">
                    <h2 class="card__title"><span>Requirements</span><svg class="ic"><use href="#i-file"/></svg></h2>
                    @if ($information['requirements'] === [])
                        <p class="muted">No requirement details were returned.</p>
                    @else
                        <ul class="doclist">
                            @foreach ($information['requirements'] as $requirement)
                                <li><svg class="ic"><use href="#i-check"/></svg><span>{{ $requirement }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </article>

                <article class="card req-card">
                    <h2 class="card__title"><span>Documents</span><svg class="ic"><use href="#i-passport"/></svg></h2>
                    @if ($information['documents'] === [])
                        <p class="muted">No document types were returned.</p>
                    @else
                        <ul class="doclist">
                            @foreach ($information['documents'] as $document)
                                <li><svg class="ic"><use href="#i-check"/></svg><span>{{ $document }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            </div>

            <div class="notice-box notice-box--warn">
                <svg class="ic"><use href="#i-shield"/></svg>
                <p>Requirements can change. This information does not guarantee approval, boarding, or admission.</p>
            </div>

            <div class="row">
                <a href="{{ route('visa.index') }}" class="btn btn--outline">Check another trip</a>
                <a href="{{ $assistanceUrl }}" class="btn btn--blue" data-track="lead">Request visa assistance</a>
            </div>
        </div>
    @endif
</div>
@endsection
