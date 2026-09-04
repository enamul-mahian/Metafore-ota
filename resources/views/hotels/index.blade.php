@extends('layouts.site')

@section('title', 'Hotels')

@section(
    'meta_description',
    'Hotel search services from Eagle Global Hub LTD.'
)

@section('content')

    <main class="travel-module-page">
        <header class="travel-module-header">
            <span class="site-eyebrow">HOTELS</span>
            <h1>Find a stay for your journey</h1>
            <p>
                Search configured hotel inventory and review room and rate
                details before continuing to guest information.
            </p>
        </header>

        <ol class="travel-module-steps" aria-label="Hotel booking steps">
            @foreach ([
                'Search',
                'Results',
                'Hotel details',
                'Room & rate',
                'Guest details',
                'Review',
                'Booking',
                'Payment',
                'Confirmation',
            ] as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ol>

        @if (! $service['available'])
            <section class="travel-unavailable" role="status">
                <span class="travel-status-badge">Not Configured</span>
                <h2>Hotel service is not configured</h2>
                <p>
                    Hotel search will be available after an approved provider
                    adapter and its required server configuration are enabled.
                </p>
                <a href="{{ route('home') }}" class="site-button site-button-secondary">
                    Back to travel services
                </a>
            </section>
        @else
            <section class="travel-search-panel">
                <div>
                    <span class="site-eyebrow">HOTEL SEARCH</span>
                    <h2>Search available stays</h2>
                </div>

                <form method="POST" action="{{ route('hotels.search') }}">
                    @csrf

                    <label class="travel-field travel-field-wide">
                        <span>Destination</span>
                        <input
                            type="text"
                            name="destination"
                            value="{{ old('destination') }}"
                            maxlength="120"
                            autocomplete="off"
                            required
                        >
                    </label>

                    <label class="travel-field">
                        <span>Check in</span>
                        <input
                            type="date"
                            name="check_in"
                            value="{{ old('check_in') }}"
                            min="{{ now()->toDateString() }}"
                            required
                        >
                    </label>

                    <label class="travel-field">
                        <span>Check out</span>
                        <input
                            type="date"
                            name="check_out"
                            value="{{ old('check_out') }}"
                            min="{{ now()->addDay()->toDateString() }}"
                            required
                        >
                    </label>

                    <label class="travel-field">
                        <span>Adults</span>
                        <select name="adults" required>
                            @for ($adults = 1; $adults <= 9; $adults++)
                                <option value="{{ $adults }}">
                                    {{ $adults }}
                                </option>
                            @endfor
                        </select>
                    </label>

                    <label class="travel-field">
                        <span>Rooms</span>
                        <select name="rooms" required>
                            @for ($rooms = 1; $rooms <= 5; $rooms++)
                                <option value="{{ $rooms }}">
                                    {{ $rooms }}
                                </option>
                            @endfor
                        </select>
                    </label>

                    <button type="submit" class="site-button site-button-primary">
                        Search Hotels
                    </button>
                </form>
            </section>
        @endif
    </main>

@endsection
