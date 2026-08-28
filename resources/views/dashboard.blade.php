<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard | MetaFore OTA</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="dashboard-body">

    <header class="dashboard-header">
        <a href="{{ route('dashboard') }}" class="dashboard-logo">
            <span class="dashboard-logo-icon">✈</span>

            <span>MetaFore OTA</span>
        </a>

        <div class="dashboard-user-area">
            <div class="dashboard-user">
                <div class="dashboard-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>

                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>{{ auth()->user()->email }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="dashboard-logout">
                    Logout
                </button>
            </form>
        </div>
    </header>

    <main class="dashboard-container">

        <section class="dashboard-welcome">
            <div>
                <span class="dashboard-kicker">
                    WELCOME TO METAFORE OTA
                </span>

                <h1>
                    Hello, {{ auth()->user()->name }} 👋
                </h1>

                <p>
                    Your travel dashboard is ready. Booking services and
                    account features will appear here as development continues.
                </p>
            </div>

            <div class="dashboard-verified">
                <span>✓</span>

                <div>
                    <strong>Email Verified</strong>
                    <small>Your account is active and secured.</small>
                </div>
            </div>
        </section>

        <section class="dashboard-section">
            <div class="dashboard-section-heading">
                <div>
                    <span class="dashboard-kicker">TRAVEL SERVICES</span>
                    <h2>Explore Services</h2>
                </div>
            </div>

            <div class="dashboard-service-grid">

                <article class="dashboard-service-card">
                    <div class="service-icon">✈</div>

                    <h3>Flights</h3>

                    <p>
                        Search and book domestic and international flights.
                    </p>

                    <span class="service-status">Coming Soon</span>
                </article>

                <article class="dashboard-service-card">
                    <div class="service-icon">⌂</div>

                    <h3>Hotels</h3>

                    <p>
                        Discover hotels and accommodation for your journey.
                    </p>

                    <span class="service-status">Coming Soon</span>
                </article>

                <article class="dashboard-service-card">
                    <div class="service-icon">⌖</div>

                    <h3>Tours</h3>

                    <p>
                        Explore curated tour packages and travel experiences.
                    </p>

                    <span class="service-status">Coming Soon</span>
                </article>

                <article class="dashboard-service-card">
                    <div class="service-icon">✓</div>

                    <h3>Visa</h3>

                    <p>
                        Manage visa assistance and application services.
                    </p>

                    <span class="service-status">Coming Soon</span>
                </article>

            </div>
        </section>

        <section class="dashboard-account-card">

            <div>
                <span class="dashboard-kicker">YOUR ACCOUNT</span>

                <h2>Account Overview</h2>
            </div>

            <div class="dashboard-account-grid">

                <div>
                    <span>Full Name</span>
                    <strong>{{ auth()->user()->name }}</strong>
                </div>

                <div>
                    <span>Email Address</span>
                    <strong>{{ auth()->user()->email }}</strong>
                </div>

                <div>
                    <span>Email Status</span>
                    <strong class="verified-text">
                        Verified
                    </strong>
                </div>

                <div>
                    <span>Account Type</span>
                    <strong>Customer</strong>
                </div>

            </div>

        </section>

    </main>

</body>
</html>