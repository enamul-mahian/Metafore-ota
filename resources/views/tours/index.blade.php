@extends('layouts.site')

@section('title', 'Tours')

@section(
    'meta_description',
    'Tour search services from Eagle Global Hub LTD.'
)

@section('content')

    <main class="travel-module-page">
        <header class="travel-module-header">
            <span class="site-eyebrow">TOURS</span>
            <h1>Explore tours for your destination</h1>
            <p>
                Search a configured tour provider and review genuine
                availability before entering traveler details.
            </p>
        </header>

        <ol
            class="travel-module-steps travel-module-steps-eight"
            aria-label="Tour booking steps"
        >
            @foreach ([
                'Search',
                'Tour details',
                'Availability',
                'Traveler details',
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
                <h2>Tour service is not configured</h2>
                <p>
                    Tour search will be available after an approved provider
                    adapter and its required server configuration are enabled.
                </p>
                <a href="{{ route('home') }}" class="site-button site-button-secondary">
                    Back to travel services
                </a>
            </section>
        @else
            <section class="travel-search-panel">
                <div>
                    <span class="site-eyebrow">TOUR SEARCH</span>
                    <h2>Search available tours</h2>
                </div>

                <form
                    method="POST"
                    action="{{ route('tours.search') }}"
                    class="travel-search-form-compact"
                >
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
                        <span>Preferred date</span>
                        <input
                            type="date"
                            name="travel_date"
                            value="{{ old('travel_date') }}"
                            min="{{ now()->toDateString() }}"
                        >
                    </label>

                    <label class="travel-field">
                        <span>Travelers</span>
                        <select name="travelers" required>
                            @for ($travelers = 1; $travelers <= 12; $travelers++)
                                <option value="{{ $travelers }}">
                                    {{ $travelers }}
                                </option>
                            @endfor
                        </select>
                    </label>

                    <button type="submit" class="site-button site-button-primary">
                        Search Tours
                    </button>
                </form>
            </section>
        @endif
    </main>

@endsection
