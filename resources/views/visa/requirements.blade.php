@extends('layouts.site')

@section('title', 'Visa Requirements')

@section('content')

    <main class="travel-module-page">
        <header class="travel-module-header travel-module-header-compact">
            <span class="site-eyebrow">VISA INFORMATION</span>
            <h1>{{ $criteria['destination_country'] }}</h1>
            <p>
                Passport: {{ $criteria['nationality'] }}
                &middot;
                {{ $criteria['origin_country'] }}
                to
                {{ $criteria['destination_country'] }}
            </p>
            <p>
                Departure:
                {{ $criteria['departure_date'] }}
                {{ $criteria['departure_time'] }}
                &middot;
                Arrival:
                {{ $criteria['arrival_date'] }}
                {{ $criteria['arrival_time'] }}
            </p>
        </header>

        @if (
            $information['summary'] === '' &&
            $information['requirements'] === [] &&
            $information['documents'] === []
        )
            <section class="travel-empty-state" role="status">
                <h2>No visa information was returned</h2>
                <p>
                    The configured source returned no visa requirements for
                    this trip. Eligibility, documents and approval have not
                    been assumed. Do not submit an application based on
                    missing information.
                </p>
                <a
                    href="{{ route('visa.index') }}"
                    class="site-button site-button-secondary"
                >
                    Check another trip
                </a>
            </section>
        @else
            @if ($information['summary'] !== '')
                <section class="travel-empty-state" role="status">
                    <h2>Trip summary</h2>
                    <p>{{ $information['summary'] }}</p>
                </section>
            @endif

            <section class="travel-information-grid">
                <article>
                    <h2>Requirements</h2>

                    @if ($information['requirements'] === [])
                        <p>No requirement details were returned.</p>
                    @else
                        <ul>
                            @foreach (
                                $information['requirements']
                                as $requirement
                            )
                                <li>{{ $requirement }}</li>
                            @endforeach
                        </ul>
                    @endif
                </article>

                <article>
                    <h2>Documents</h2>

                    @if ($information['documents'] === [])
                        <p>No document types were returned.</p>
                    @else
                        <ul>
                            @foreach (
                                $information['documents']
                                as $document
                            )
                                <li>{{ $document }}</li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            </section>

            <section class="travel-empty-state">
                <p>
                    Requirements can change. This information does not
                    guarantee approval, boarding, or admission.
                </p>
            </section>
        @endif
    </main>

@endsection