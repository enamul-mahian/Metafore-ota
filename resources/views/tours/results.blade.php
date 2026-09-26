@extends('layouts.site')

@section('seo_title', 'Tours in '.$criteria['destination'].' | Eagle Global Hub')
@section('robots', 'noindex,follow')

@php
    $quoteBase = array_filter([
        'service' => 'tours',
        'destination' => $criteria['destination'],
        'travel_date' => $criteria['travel_date'] ?? null,
        'guests' => $criteria['travelers'],
    ]);
@endphp

@section('content')
<section class="searchbar-wrap">
    <div class="container">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span><a href="{{ route('tours.index') }}">Tours &amp; Activities</a></span><span>{{ $criteria['destination'] }}</span></nav>
    </div>
</section>

<section class="page" style="padding-top:8px">
    <div class="container layout-2">
        <aside class="filters" id="tourFilters" aria-label="Modify tour search">
            <div class="filters__head">Modify search <button type="button" class="icon-btn filters__close" data-toggle="tourFilters" aria-label="Close search"><svg class="ic"><use href="#i-x"/></svg></button></div>
            <div class="filters__group">
                @include('tours._search-form', ['criteria' => $criteria, 'stacked' => true])
            </div>
        </aside>
        <div class="backdrop" data-toggle="tourFilters" aria-hidden="true"></div>

        <div>
            <div class="results-head">
                <div>
                    <h1 class="h1">Tours &amp; Activities in {{ $criteria['destination'] }}</h1>
                    <p>{{ $criteria['travelers'] }} traveler(s)@if ($criteria['travel_date'] ?? null) &middot; {{ $criteria['travel_date'] }}@endif @if ($tours !== []) &middot; {{ count($tours) }} {{ Str::plural('tour', count($tours)) }} returned @endif</p>
                </div>
                <button type="button" class="btn btn--outline btn--sm filters-toggle" data-toggle="tourFilters"><svg class="ic"><use href="#i-edit"/></svg>Modify search</button>
            </div>

            @if ($tours === [])
                <section class="card empty" role="status">
                    <svg class="ic"><use href="#i-palm"/></svg>
                    <h2 class="h3">No tours were returned</h2>
                    <p>The configured provider returned no matching tours. No availability, price or booking has been assumed.</p>
                    <div class="row" style="justify-content:center;margin-top:14px">
                        <a href="{{ route('tours.index') }}" class="btn btn--outline">Change search</a>
                        <a href="{{ route('enquiries.create', $quoteBase) }}" class="btn btn--blue">Ask our team instead</a>
                    </div>
                </section>
            @else
                <div data-list aria-label="Tour results">
                    @foreach ($tours as $tour)
                        <article class="hcard hcard--plain">
                            <span class="hcard__ic c-green" aria-hidden="true"><svg class="ic"><use href="#i-palm"/></svg></span>
                            <div class="hcard__body">
                                <h2 class="h3">{{ $tour['title'] }}</h2>
                                @if ($tour['location'] !== '')
                                    <p class="loc"><svg class="ic"><use href="#i-pin"/></svg>{{ $tour['location'] }}</p>
                                @endif
                                @if ($tour['summary'] !== '')
                                    <p>{{ Str::limit($tour['summary'], 260) }}</p>
                                @endif
                            </div>
                            <div class="hcard__price">
                                <small>Availability and price are confirmed by our team before you pay.</small>
                                <a class="btn btn--blue" data-track="lead" href="{{ route('enquiries.create', [...$quoteBase, 'message' => 'I would like to book: '.$tour['title'].($tour['location'] !== '' ? ', '.$tour['location'] : '')]) }}">Request booking</a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p class="hint results-note">Online tour booking is not available yet. "Request booking" sends the tour and your details to our team, who confirm availability and price with you.</p>
            @endif
        </div>
    </div>
</section>
@endsection
