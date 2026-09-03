<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Eagle Global Hub LTD')</title>

    <meta
        name="description"
        content="Secure account access for Eagle Global Hub LTD flight and travel services."
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-body">

    <main class="auth-shell">

        <section class="auth-visual">

            <div class="auth-visual-top">
                <a href="{{ route('home') }}" class="auth-brand">
                    <span class="auth-brand-icon" aria-hidden="true">
                        &#9992;
                    </span>

                    <span>
                        <strong>Eagle Global Hub LTD</strong>
                        <small>Flights & Travel</small>
                    </span>
                </a>

                <a href="{{ route('home') }}" class="auth-home-link">
                    Back to website
                </a>
            </div>

            <div class="auth-visual-content">

                <span class="auth-eyebrow">
                    SECURE TRAVEL ACCOUNT
                </span>

                <h1>
                    @yield(
                        'hero-title',
                        'Your journey starts with secure account access.'
                    )
                </h1>

                <p>
                    @yield(
                        'hero-description',
                        'Search, review and manage your flight journey from one verified account.'
                    )
                </p>

                <div class="auth-trust-list">

                    <div>
                        <span aria-hidden="true">&#10003;</span>

                        <p>
                            <strong>Verified account access</strong>
                            <small>
                                Protected areas require authenticated and verified access.
                            </small>
                        </p>
                    </div>

                    <div>
                        <span aria-hidden="true">&#10003;</span>

                        <p>
                            <strong>Clear booking flow</strong>
                            <small>
                                Review important itinerary and booking states as you continue.
                            </small>
                        </p>
                    </div>

                    <div>
                        <span aria-hidden="true">&#10003;</span>

                        <p>
                            <strong>Server-authoritative actions</strong>
                            <small>
                                Sensitive booking and payment decisions remain server controlled.
                            </small>
                        </p>
                    </div>

                </div>

            </div>

            <small class="auth-visual-footer">
                Eagle Global Hub LTD
            </small>

        </section>

        <section class="auth-content">

            <div class="auth-mobile-brand">
                <a href="{{ route('home') }}">
                    <span class="auth-brand-icon" aria-hidden="true">
                        &#9992;
                    </span>

                    <span>
                        <strong>Eagle Global Hub LTD</strong>
                        <small>Flights & Travel</small>
                    </span>
                </a>
            </div>

            <div class="auth-card">

                @if ($errors->any())
                    <div
                        class="auth-alert auth-alert-error"
                        role="alert"
                    >
                        <strong>
                            Please check the form.
                        </strong>

                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (
                    session('status')
                    && session('status') !== 'verification-link-sent'
                )
                    <div
                        class="auth-alert auth-alert-success"
                        role="status"
                    >
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')

                <div class="auth-card-meta">
                    <span>Secure account access</span>

                    <a href="{{ route('home') }}">
                        Eagle Global Hub LTD
                    </a>
                </div>

            </div>

        </section>

    </main>

</body>
</html>