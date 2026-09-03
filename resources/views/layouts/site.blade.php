<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Travel') | Eagle Global Hub LTD</title>

    <meta
        name="description"
        content="@yield('meta_description', 'Flight search and travel booking services from Eagle Global Hub LTD.')"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>

<body class="site-body @yield('body_class')">

    <a href="#main-content" class="site-skip-link">
        Skip to content
    </a>

    <header class="site-header">
        <div class="site-header-inner">

            <a href="{{ route('home') }}" class="site-brand">
                <span class="site-brand-mark" aria-hidden="true">
                    &#9992;
                </span>

                <span class="site-brand-copy">
                    <strong>Eagle Global Hub LTD</strong>
                    <small>Flights & Travel</small>
                </span>
            </a>

            <nav class="site-nav" aria-label="Primary navigation">

                <a
                    href="{{ route('home') }}"
                    @class([
                        'is-active' => request()->routeIs('home')
                    ])
                >
                    Home
                </a>

                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        @class([
                            'is-active' => request()->routeIs('dashboard')
                        ])
                    >
                        Dashboard
                    </a>

                    <a
                        href="{{ route('account.overview') }}"
                        @class([
                            'is-active' => request()->routeIs('account.*')
                        ])
                    >
                        Account
                    </a>
                    @can('flights.search')
                        <a
                            href="{{ route('flights.index') }}"
                            @class([
                                'is-active' => request()->routeIs('flights.*')
                            ])
                        >
                            Flights
                        </a>
                    @endcan
                @endauth

            </nav>

            <div class="site-actions">

                @auth
                    <div class="site-user">
                        <span class="site-user-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>

                        <span class="site-user-copy">
                            <strong>
                                {{ auth()->user()->name }}
                            </strong>

                            <small>
                                {{ auth()->user()->email }}
                            </small>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            type="submit"
                            class="site-button site-button-ghost"
                        >
                            Logout
                        </button>
                    </form>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="site-button site-button-ghost"
                    >
                        Login
                    </a>

                    <a
                        href="{{ route('register') }}"
                        class="site-button site-button-primary"
                    >
                        Create Account
                    </a>
                @endauth

            </div>
        </div>
    </header>

    @if (app()->environment(['local', 'testing']))
        <div
            class="site-environment-banner"
            role="status"
        >
            <strong>Development / sandbox environment:</strong>
            flight availability may use fixture or supplier sandbox data and
            must not be treated as live bookable airline inventory.
        </div>
    @endif

    <div id="main-content">
        @yield('content')
    </div>

    <footer class="site-footer">
        <div class="site-footer-inner">

            <div>
                <a
                    href="{{ route('home') }}"
                    class="site-footer-brand"
                >
                    Eagle Global Hub LTD
                </a>

                <p>
                    A clear and secure flight-booking journey from search
                    through booking status and confirmation.
                </p>
            </div>

            <div class="site-footer-links">
                <a href="{{ route('home') }}">Home</a>

                @auth
                    <a href="{{ route('dashboard') }}">Dashboard</a>

                    @can('flights.search')
                        <a href="{{ route('flights.index') }}">Flights</a>
                    @endcan
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}">Register</a>
                @endauth
            </div>

            <small>
                &copy; {{ now()->year }} Eagle Global Hub LTD.
                Live supplier inventory and execution require verified
                production configuration.
            </small>

        </div>
    </footer>

    @stack('scripts')

</body>
</html>