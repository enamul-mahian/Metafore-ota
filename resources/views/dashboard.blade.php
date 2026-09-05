@php
    $isAdminDashboard = auth()->user()->hasAnyRole(['admin', 'super-admin']);
@endphp

@extends($isAdminDashboard ? 'layouts.admin' : 'layouts.site')

@section('title', 'Dashboard')
@section('body_class', 'dashboard-body')
@section('page-class', 'admin-page')

@section('content')

    @if ($isAdminDashboard)
        <x-admin.page-header
            title="Dashboard"
            description="Open the operational areas available to your assigned administration role."
            icon="D"
            eyebrow="Administration overview"
        >
            <a class="egh-button secondary" href="{{ route('home') }}">View website</a>
        </x-admin.page-header>

        <section class="egh-card">
            <div class="admin-card-heading">
                <div>
                    <span class="admin-page-eyebrow">Workspace</span>
                    <h2>Administration areas</h2>
                    <p>Every link below follows the same permission checks as the admin navigation.</p>
                </div>
            </div>

            <div class="admin-dashboard-grid">
                @can('users.view')
                    <a class="admin-dashboard-card" href="{{ route('admin.users.index') }}">
                        <strong>Users</strong>
                        <span>Accounts, verification, and role assignments</span>
                    </a>
                @endcan

                @can('roles.view')
                    <a class="admin-dashboard-card" href="{{ route('admin.roles.index') }}">
                        <strong>Roles &amp; Permissions</strong>
                        <span>Authorization roles and permission coverage</span>
                    </a>
                @endcan

                @role('super-admin')
                    <a class="admin-dashboard-card" href="{{ route('admin.features.index') }}">
                        <strong>Feature Control</strong>
                        <span>Visibility controls within established safety boundaries</span>
                    </a>
                @endrole

                @can('settings.view')
                    <a class="admin-dashboard-card" href="{{ route('admin.settings.manage') }}">
                        <strong>Settings</strong>
                        <span>Application configuration available to your role</span>
                    </a>
                @endcan

                @can('master-data.view')
                    <a class="admin-dashboard-card" href="{{ route('admin.master-data.manage') }}">
                        <strong>Master Data</strong>
                        <span>Countries, cities, categories, currencies, and languages</span>
                    </a>
                @endcan

                <a class="admin-dashboard-card" href="{{ route('admin.bookings.index') }}">
                    <strong>Bookings</strong>
                    <span>Read-only persisted flight order attempts</span>
                </a>

                @can('reports.view')
                    <a class="admin-dashboard-card" href="{{ route('admin.reports.index') }}">
                        <strong>Reports</strong>
                        <span>Read-only operational reporting</span>
                    </a>
                @endcan

                @can('system-logs.view')
                    <a class="admin-dashboard-card" href="{{ route('admin.system-logs.index') }}">
                        <strong>System Logs</strong>
                        <span>Redacted, metadata-only application events</span>
                    </a>
                @endcan
            </div>
        </section>
    @else

    <main class="dashboard-container">

        <section class="dashboard-welcome">

            <div>
                <span class="dashboard-kicker">
                    WELCOME BACK
                </span>

                <h1>
                    Hello, {{ auth()->user()->name }}
                </h1>

                <p>
                    Search flights, review your account details and continue
                    your travel journey from one secure dashboard.
                </p>

                <div class="dashboard-welcome-actions">

                    @feature('flights')
                        @can('flights.search')
                            <a
                                href="{{ route('flights.index') }}"
                                class="site-button site-button-primary"
                            >
                                Search Flights
                            </a>
                        @endcan
                    @endfeature

                    @feature('account')
                        <a
                            href="{{ route('account.overview') }}"
                            class="site-button site-button-secondary"
                        >
                            View Account
                        </a>
                    @endfeature

                </div>
            </div>

            <div class="dashboard-verified">
                <span aria-hidden="true">&#10003;</span>

                <div>
                    <strong>Email Verified</strong>

                    <small>
                        Your verified account can access protected travel pages.
                    </small>
                </div>
            </div>

        </section>

        <section class="dashboard-section">

            <div class="dashboard-section-heading">
                <div>
                    <span class="dashboard-kicker">
                        QUICK ACCESS
                    </span>

                    <h2>
                        Continue your journey
                    </h2>
                </div>
            </div>

            <div class="dashboard-quick-grid">

                @feature('flights')
                    @can('flights.search')
                        <a
                            href="{{ route('flights.index') }}"
                            class="dashboard-quick-card"
                        >
                        <span class="dashboard-quick-icon" aria-hidden="true">
                            &#9992;
                        </span>

                        <div>
                            <strong>Flight Search</strong>

                            <small>
                                Search available flight options by route,
                                date, cabin and travelers.
                            </small>
                        </div>

                        <b aria-hidden="true">&rarr;</b>
                        </a>
                    @endcan
                @endfeature

                @feature('account')
                    <a
                        href="{{ route('account.overview') }}"
                        class="dashboard-quick-card"
                    >
                    <span class="dashboard-quick-icon" aria-hidden="true">
                        A
                    </span>

                    <div>
                        <strong>Account Overview</strong>

                        <small>
                            Review your name, email, verification and
                            account timeline.
                        </small>
                    </div>

                    <b aria-hidden="true">&rarr;</b>
                    </a>
                @endfeature

                @feature('bookings')
                    @can('flights.book')
                        <a
                            href="{{ route('bookings.index') }}"
                            class="dashboard-quick-card"
                        >
                        <span class="dashboard-quick-icon" aria-hidden="true">
                            B
                        </span>

                        <div>
                            <strong>My Bookings</strong>

                            <small>
                                Review your flight booking attempts, order
                                status and payment status.
                            </small>
                        </div>

                        <b aria-hidden="true">&rarr;</b>
                        </a>
                    @endcan
                @endfeature

            </div>

        </section>

        <section class="dashboard-section">

            <div class="dashboard-section-heading">
                <div>
                    <span class="dashboard-kicker">
                        TRAVEL SERVICES
                    </span>

                    <h2>
                        Services
                    </h2>
                </div>
            </div>

            <div class="dashboard-service-grid">

                @feature('flights')
                    @can('flights.search')
                        <a
                            href="{{ route('flights.index') }}"
                            class="dashboard-service-card dashboard-service-card-link"
                        >
                        <div class="service-icon" aria-hidden="true">
                            &#9992;
                        </div>

                        <h3>Flights</h3>

                        <p>
                            Search domestic and international flight options.
                        </p>

                        <span class="service-status service-status-live">
                            Available
                        </span>
                        </a>
                    @else
                        <article class="dashboard-service-card">
                        <div class="service-icon" aria-hidden="true">
                            &#9992;
                        </div>

                        <h3>Flights</h3>

                        <p>
                            Flight access is not enabled for this account.
                        </p>

                        <span class="service-status">
                            Unavailable
                        </span>
                        </article>
                    @endcan
                @endfeature

                @foreach ([
                    'hotels' => [
                        'icon' => 'H',
                        'description' => 'Search configured hotel availability and stay options.',
                    ],
                    'tours' => [
                        'icon' => 'T',
                        'description' => 'Search configured tours and destination activities.',
                    ],
                    'visa' => [
                        'icon' => 'V',
                        'description' => 'Review configured visa and entry requirement information.',
                    ],
                ] as $serviceKey => $presentation)
                    @feature($serviceKey)
                        @php
                            $service = $travelServices[$serviceKey] ?? null;
                            $hasPermission = $service
                                && (
                                    $service['permission'] === null
                                    || auth()->user()->can($service['permission'])
                                );
                            $canAccess = $service
                                && $service['available']
                                && $service['route_name']
                                && $hasPermission;
                        @endphp

                        @if ($canAccess)
                            <a
                                href="{{ route($service['route_name']) }}"
                                class="dashboard-service-card dashboard-service-card-link"
                            >
                                <div class="service-icon" aria-hidden="true">
                                    {{ $presentation['icon'] }}
                                </div>

                                <h3>{{ $service['label'] }}</h3>

                                <p>{{ $presentation['description'] }}</p>

                                <span class="service-status service-status-live">
                                    {{ $service['status'] }}
                                </span>
                            </a>
                        @else
                            <article class="dashboard-service-card">
                                <div class="service-icon" aria-hidden="true">
                                    {{ $presentation['icon'] }}
                                </div>

                                <h3>{{ $service['label'] ?? ucfirst($serviceKey) }}</h3>

                                <p>
                                    @if ($service && $service['available'])
                                        This service is not enabled for your account.
                                    @else
                                        This service is not configured for customer use.
                                    @endif
                                </p>

                                <span class="service-status">
                                    {{ $service && $service['available']
                                        ? 'Unavailable'
                                        : ($service['status'] ?? 'Not Configured') }}
                                </span>
                            </article>
                        @endif
                    @endfeature
                @endforeach

            </div>

        </section>

        <section class="dashboard-account-card">

            <div class="dashboard-account-heading">
                <div>
                    <span class="dashboard-kicker">
                        YOUR ACCOUNT
                    </span>

                    <h2>
                        Account snapshot
                    </h2>
                </div>

                @feature('account')
                    <a
                        href="{{ route('account.overview') }}"
                        class="dashboard-account-link"
                    >
                        Open Account
                    </a>
                @endfeature
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
                    <span>Member Since</span>

                    <strong>
                        {{ auth()->user()->created_at?->format('M Y') ?? 'Not available' }}
                    </strong>
                </div>

            </div>

        </section>

    </main>

    @endif

@endsection
