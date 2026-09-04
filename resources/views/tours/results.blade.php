@extends('layouts.site')

@section('title', 'Tour Results')

@section('content')

    <main class="travel-module-page">
        <header class="travel-module-header travel-module-header-compact">
            <span class="site-eyebrow">TOUR RESULTS</span>
            <h1>{{ $criteria['destination'] }}</h1>
            <p>
                {{ $criteria['travelers'] }} traveler(s)
                @if ($criteria['travel_date'] ?? null)
                    &middot; {{ $criteria['travel_date'] }}
                @endif
            </p>
        </header>

        @if ($tours === [])
            <section class="travel-empty-state" role="status">
                <h2>No tours were returned</h2>
                <p>
                    The configured provider returned no matching tours. No
                    availability, price or booking has been assumed.
                </p>
                <a href="{{ route('tours.index') }}" class="site-button site-button-secondary">
                    Change search
                </a>
            </section>
        @else
            <section class="travel-result-list" aria-label="Tour results">
                @foreach ($tours as $tour)
                    <article>
                        <div>
                            <h2>{{ $tour['title'] }}</h2>
                            <p>{{ $tour['location'] }}</p>
                            @if ($tour['summary'] !== '')
                                <small>{{ $tour['summary'] }}</small>
                            @endif
                        </div>
                        <span>Availability requires provider integration</span>
                    </article>
                @endforeach
            </section>
        @endif
    </main>

@endsection
