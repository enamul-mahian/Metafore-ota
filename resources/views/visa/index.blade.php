@extends('layouts.site')

@section('title', 'Visa Services')

@section(
    'meta_description',
    'Visa information services from Eagle Global Hub LTD.'
)

@section('content')

    <main class="travel-module-page">
        <header class="travel-module-header">
            <span class="site-eyebrow">VISA SERVICES</span>
            <h1>Review visa requirements for your trip</h1>
            <p>
                Check travel-document information from a configured source
                using your passport nationality and actual journey details.
                Approval and entry are never guaranteed.
            </p>
        </header>

        <ol
            class="travel-module-steps travel-module-steps-seven"
            aria-label="Visa information steps"
        >
            @foreach ([
                'Trip details',
                'Requirements',
                'Application options',
                'Documents',
                'Review',
                'Submission',
                'Status',
            ] as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ol>

        @if (! $service['available'])
            <section class="travel-unavailable" role="status">
                <span class="travel-status-badge">Not Configured</span>
                <h2>Visa information service is not configured</h2>
                <p>
                    Visa requirements will be available only after an approved
                    information provider and its required server configuration
                    are enabled.
                </p>
                <a
                    href="{{ route('home') }}"
                    class="site-button site-button-secondary"
                >
                    Back to travel services
                </a>
            </section>
        @elseif ($countries->isEmpty())
            <section class="travel-unavailable" role="status">
                <span class="travel-status-badge">Unavailable</span>
                <h2>Country information is unavailable</h2>
                <p>
                    Visa lookup cannot continue until the active country
                    catalogue is available.
                </p>
            </section>
        @else
            <section class="travel-search-panel">
                <div>
                    <span class="site-eyebrow">REQUIREMENTS</span>
                    <h2>Check your trip requirements</h2>
                </div>

                <form
                    method="POST"
                    action="{{ route('visa.requirements') }}"
                    class="travel-search-form-compact"
                >
                    @csrf

                    <label class="travel-field">
                        <span>Passport nationality</span>
                        <select name="nationality" required>
                            <option value="">Select country</option>
                            @foreach ($countries as $country)
                                <option
                                    value="{{ $country->iso3 }}"
                                    @selected(
                                        old('nationality') === $country->iso3
                                    )
                                >
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="travel-field">
                        <span>Origin country</span>
                        <select name="origin_country" required>
                            <option value="">Select country</option>
                            @foreach ($countries as $country)
                                <option
                                    value="{{ $country->iso3 }}"
                                    @selected(
                                        old('origin_country') === $country->iso3
                                    )
                                >
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="travel-field">
                        <span>Destination country</span>
                        <select name="destination_country" required>
                            <option value="">Select country</option>
                            @foreach ($countries as $country)
                                <option
                                    value="{{ $country->iso3 }}"
                                    @selected(
                                        old('destination_country') === $country->iso3
                                    )
                                >
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="travel-field">
                        <span>Departure date</span>
                        <input
                            type="date"
                            name="departure_date"
                            value="{{ old('departure_date') }}"
                            min="{{ now()->toDateString() }}"
                            required
                        >
                    </label>

                    <label class="travel-field">
                        <span>Departure time</span>
                        <input
                            type="time"
                            name="departure_time"
                            value="{{ old('departure_time') }}"
                            required
                        >
                    </label>

                    <label class="travel-field">
                        <span>Arrival date</span>
                        <input
                            type="date"
                            name="arrival_date"
                            value="{{ old('arrival_date') }}"
                            min="{{ now()->toDateString() }}"
                            required
                        >
                    </label>

                    <label class="travel-field">
                        <span>Arrival time</span>
                        <input
                            type="time"
                            name="arrival_time"
                            value="{{ old('arrival_time') }}"
                            required
                        >
                    </label>

                    <button
                        type="submit"
                        class="site-button site-button-primary"
                    >
                        Check Requirements
                    </button>
                </form>

                <p>
                    This service provides travel-requirement information only.
                    It does not guarantee visa approval or admission at the
                    border.
                </p>
            </section>
        @endif
    </main>

@endsection