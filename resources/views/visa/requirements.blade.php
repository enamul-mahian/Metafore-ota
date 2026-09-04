@extends('layouts.site')

@section('title', 'Visa Requirements')

@section('content')

    <main class="travel-module-page">
        <header class="travel-module-header travel-module-header-compact">
            <span class="site-eyebrow">VISA INFORMATION</span>
            <h1>{{ $criteria['destination_country'] }}</h1>
            <p>
                Nationality: {{ $criteria['nationality'] }}
                &middot; Type: {{ $criteria['visa_type'] }}
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
                    The configured source returned no requirements for this
                    request. Eligibility, documents and approval have not been
                    assumed. Do not submit an application based on missing
                    information.
                </p>
                <a href="{{ route('visa.index') }}" class="site-button site-button-secondary">
                    Check another request
                </a>
            </section>
        @else
            <section class="travel-information-grid">
                <article>
                    <h2>Requirements</h2>
                    @if ($information['requirements'] === [])
                        <p>No requirements were returned.</p>
                    @else
                        <ul>
                            @foreach ($information['requirements'] as $requirement)
                                <li>{{ $requirement }}</li>
                            @endforeach
                        </ul>
                    @endif
                </article>

                <article>
                    <h2>Documents</h2>
                    @if ($information['documents'] === [])
                        <p>No document list was returned.</p>
                    @else
                        <ul>
                            @foreach ($information['documents'] as $document)
                                <li>{{ $document }}</li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            </section>
        @endif
    </main>

@endsection
