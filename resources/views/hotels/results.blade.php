@extends('layouts.site')

@section('title', 'Hotel Results')

@section('content')

    <main class="travel-module-page">
        <header class="travel-module-header travel-module-header-compact">
            <span class="site-eyebrow">HOTEL RESULTS</span>
            <h1>{{ $criteria['destination'] }}</h1>
            <p>
                {{ $criteria['check_in'] }} to {{ $criteria['check_out'] }}
                &middot; {{ $criteria['adults'] }} adult(s)
                &middot; {{ $criteria['rooms'] }} room(s)
            </p>
        </header>

        @if ($hotels === [])
            <section class="travel-empty-state" role="status">
                <h2>No hotel stays were returned</h2>
                <p>
                    The configured provider returned no matching inventory for
                    this search. No availability or price has been assumed.
                </p>
                <a href="{{ route('hotels.index') }}" class="site-button site-button-secondary">
                    Change search
                </a>
            </section>
        @else
            <section class="travel-result-list" aria-label="Hotel results">
                @foreach ($hotels as $hotel)
                    <article>
                        <div>
                            <h2>{{ $hotel['name'] }}</h2>
                            <p>{{ $hotel['location'] }}</p>
                            @if ($hotel['summary'] !== '')
                                <small>{{ $hotel['summary'] }}</small>
                            @endif
                        </div>
                        <span>Details require provider integration</span>
                    </article>
                @endforeach
            </section>
        @endif
    </main>

@endsection
