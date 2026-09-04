@extends('layouts.site')

@section('title', 'Visa Services')

@section(
    'meta_description',
    'Visa information and application services from Eagle Global Hub LTD.'
)

@section('content')

    <main class="travel-module-page">
        <header class="travel-module-header">
            <span class="site-eyebrow">VISA SERVICES</span>
            <h1>Review visa information before applying</h1>
            <p>
                Check information from a configured source, review document
                requirements and submit only when the application service is
                available. Approval is never guaranteed.
            </p>
        </header>

        <ol
            class="travel-module-steps travel-module-steps-seven"
            aria-label="Visa application steps"
        >
            @foreach ([
                'Country & visa type',
                'Requirements',
                'Application form',
                'Documents',
                'Review',
                'Submit',
                'Application status',
            ] as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ol>

        @if (! $service['available'])
            <section class="travel-unavailable" role="status">
                <span class="travel-status-badge">Not Configured</span>
                <h2>Visa information service is not configured</h2>
                <p>
                    Country requirements and application services will be
                    available only after an approved source and its required
                    server configuration are enabled.
                </p>
                <a href="{{ route('home') }}" class="site-button site-button-secondary">
                    Back to travel services
                </a>
            </section>
        @else
            <section class="travel-search-panel">
                <div>
                    <span class="site-eyebrow">REQUIREMENTS</span>
                    <h2>Check available visa information</h2>
                </div>

                <form
                    method="POST"
                    action="{{ route('visa.requirements') }}"
                    class="travel-search-form-compact"
                >
                    @csrf

                    <label class="travel-field">
                        <span>Nationality</span>
                        <input
                            type="text"
                            name="nationality"
                            value="{{ old('nationality') }}"
                            maxlength="120"
                            autocomplete="off"
                            required
                        >
                    </label>

                    <label class="travel-field">
                        <span>Destination country</span>
                        <input
                            type="text"
                            name="destination_country"
                            value="{{ old('destination_country') }}"
                            maxlength="120"
                            autocomplete="off"
                            required
                        >
                    </label>

                    <label class="travel-field">
                        <span>Visa type</span>
                        <input
                            type="text"
                            name="visa_type"
                            value="{{ old('visa_type') }}"
                            maxlength="80"
                            autocomplete="off"
                            required
                        >
                    </label>

                    <button type="submit" class="site-button site-button-primary">
                        Check Requirements
                    </button>
                </form>
            </section>
        @endif
    </main>

@endsection
