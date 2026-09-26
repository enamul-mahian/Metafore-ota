@extends('layouts.site')

@section('seo_title', 'Hotels in '.$criteria['destination'].' | Eagle Global Hub')
@section('robots', 'noindex,follow')

@section('content')
<section class="searchbar-wrap">
    <div class="container">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span><a href="{{ route('hotels.index') }}">Hotels</a></span><span>{{ $criteria['destination'] }}</span></nav>
    </div>
</section>

<section class="page" style="padding-top:8px">
    <div class="container layout-2">
        <aside class="filters" id="hotelFilters" aria-label="Modify hotel search">
            <div class="filters__head">Modify search <button type="button" class="icon-btn filters__close" data-toggle="hotelFilters" aria-label="Close search"><svg class="ic"><use href="#i-x"/></svg></button></div>
            <div class="filters__group">
                @include('hotels._search-form', ['criteria' => $criteria, 'stacked' => true])
            </div>
        </aside>
        <div class="backdrop" data-toggle="hotelFilters" aria-hidden="true"></div>

        <div>
            <div class="results-head">
                <div>
                    <h1 class="h1">Hotels in {{ $criteria['destination'] }}</h1>
                    <p>{{ $criteria['check_in'] }} to {{ $criteria['check_out'] }} &middot; {{ $criteria['adults'] }} adult(s) &middot; {{ $criteria['rooms'] }} room(s)@if ($hotels !== []) &middot; {{ count($hotels) }} {{ Str::plural('property', count($hotels)) }} returned @endif</p>
                </div>
                <button type="button" class="btn btn--outline btn--sm filters-toggle" data-toggle="hotelFilters"><svg class="ic"><use href="#i-edit"/></svg>Modify search</button>
            </div>

            @if ($hotels === [])
                <section class="card empty" role="status">
                    <svg class="ic"><use href="#i-hotel"/></svg>
                    <h2 class="h3">No hotel stays were returned</h2>
                    <p>The configured provider returned no matching inventory for this search. No availability or price has been assumed.</p>
                    <div class="row" style="justify-content:center;margin-top:14px">
                        <a href="{{ route('hotels.index') }}" class="btn btn--outline">Change search</a>
                        <a href="{{ route('enquiries.create', ['service' => 'hotels', 'city' => $criteria['destination'], 'check_in' => $criteria['check_in'], 'check_out' => $criteria['check_out'], 'rooms' => $criteria['rooms'], 'guests' => $criteria['adults']]) }}" class="btn btn--blue">Ask our team instead</a>
                    </div>
                </section>
            @else
                <div data-list aria-label="Hotel results">
                    @foreach ($hotels as $hotel)
                        <article class="hcard hcard--plain">
                            <span class="hcard__ic c-blue" aria-hidden="true"><svg class="ic"><use href="#i-hotel"/></svg></span>
                            <div class="hcard__body">
                                <h2 class="h3">{{ $hotel['name'] }}</h2>
                                @if ($hotel['location'] !== '')
                                    <p class="loc"><svg class="ic"><use href="#i-pin"/></svg>{{ $hotel['location'] }}</p>
                                @endif
                                @if ($hotel['summary'] !== '')
                                    <p>{{ Str::limit($hotel['summary'], 260) }}</p>
                                @endif
                            </div>
                            <div class="hcard__price">
                                <small>Rooms and rates are confirmed by our team before you pay.</small>
                                <a class="btn btn--blue" data-track="lead" href="{{ route('enquiries.create', [
                                    'service' => 'hotels',
                                    'city' => $criteria['destination'],
                                    'check_in' => $criteria['check_in'],
                                    'check_out' => $criteria['check_out'],
                                    'rooms' => $criteria['rooms'],
                                    'guests' => $criteria['adults'],
                                    'message' => 'I would like to book: '.$hotel['name'].($hotel['location'] !== '' ? ', '.$hotel['location'] : ''),
                                ]) }}">Request booking</a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p class="hint results-note">Online hotel booking is not available yet. "Request booking" sends the hotel and your dates to our team, who confirm availability and price with you.</p>
            @endif
        </div>
    </div>
</section>
@endsection
