@extends('layouts.site')

@section('title', 'Feature Unavailable')
@section('body_class', 'public-page-body')

@section('content')
    <main class="public-page-container">
        <section
            class="public-page-hero public-page-error"
            aria-labelledby="feature-unavailable-title"
        >
            <span class="public-page-kicker">UNAVAILABLE</span>

            <h1 id="feature-unavailable-title">Feature unavailable</h1>

            <p>{{ $message }}</p>

            <div class="public-page-actions">
                <a
                    href="{{ route('home') }}"
                    class="site-button site-button-primary"
                >
                    Return home
                </a>
            </div>
        </section>
    </main>
@endsection
