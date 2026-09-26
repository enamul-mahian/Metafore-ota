@php
    $isAdminDashboard = auth()->user()->hasAnyRole(['admin', 'super-admin']);
@endphp

@extends($isAdminDashboard ? 'layouts.admin' : 'layouts.site')

@section('title', 'Dashboard')
@section('body_class', 'dashboard-body')
@section('page-class', 'admin-page')
@section('robots', 'noindex,nofollow')

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

        @php
            $dashboardStats = app(\App\Services\AdminDashboardService::class);
            $kpis = $dashboardStats->kpis(auth()->user());
            $recentEnquiries = $dashboardStats->recentEnquiries(auth()->user());
        @endphp

        <section class="dash-kpis" aria-label="Key numbers">
            @foreach ($kpis as $kpi)
                @if ($kpi['url'])
                    <a href="{{ $kpi['url'] }}" class="dash-kpi dash-kpi--{{ $kpi['tone'] }}">
                @else
                    <div class="dash-kpi dash-kpi--{{ $kpi['tone'] }}">
                @endif
                    <span class="dash-kpi__label">{{ $kpi['label'] }}</span>
                    <strong class="dash-kpi__value">{{ number_format($kpi['value']) }}</strong>
                    <span class="dash-kpi__hint">{{ $kpi['hint'] }}</span>
                @if ($kpi['url'])
                    </a>
                @else
                    </div>
                @endif
            @endforeach
        </section>

        @can('enquiries.view')
            <section class="egh-card dash-recent">
                <div class="admin-card-heading">
                    <div>
                        <span class="admin-page-eyebrow">Leads</span>
                        <h2>Latest enquiries</h2>
                    </div>
                    @if (Route::has('admin.enquiries.index'))
                        <a class="egh-button secondary" href="{{ route('admin.enquiries.index') }}">All enquiries</a>
                    @endif
                </div>
                @if ($recentEnquiries->isEmpty())
                    <p class="dash-empty">No enquiries yet. Quote requests from the website will appear here.</p>
                @else
                    <div class="wsc-table-wrap">
                        <table class="wsc-table dash-table">
                            <thead><tr><th>Received</th><th>Service</th><th>Name</th><th>Status</th><th><span class="sr-only">Open</span></th></tr></thead>
                            <tbody>
                                @foreach ($recentEnquiries as $enquiry)
                                    <tr>
                                        <td>{{ $enquiry->created_at?->diffForHumans() }}</td>
                                        <td>{{ $enquiry->serviceLabel() }}</td>
                                        <td>{{ $enquiry->name }}</td>
                                        <td><span @class(['dash-status', 'is-new' => $enquiry->status === \App\Models\Enquiry::STATUS_NEW])>{{ $enquiry->statusLabel() }}</span></td>
                                        <td><a href="{{ route('admin.enquiries.show', $enquiry) }}">View</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endcan

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

                @can('content.view')
                    <a class="admin-dashboard-card" href="{{ route('admin.website.images') }}">
                        <strong>Website</strong>
                        <span>Images, destinations, SEO, tracking &amp; ads, contact details</span>
                    </a>
                @endcan

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

    <section class="page account-page">
        <div class="container">
            <nav class="crumbs" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>Dashboard</span>
            </nav>

            <div class="account-layout">
                @include('account.partials.side-menu', ['active' => ''])

                <div class="stack">
                    <div class="card account-hello">
                        <div class="account-hello__id">
                            <span class="account-hello__av" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <div>
                                <p class="account-hello__kicker">Welcome back</p>
                                <h1 class="h1">Hello, {{ auth()->user()->name }}</h1>
                                <p class="muted small">Search flights, review your account details and continue your travel journey.</p>
                            </div>
                        </div>
                        <div class="account-hello__cta">
                            @feature('flights')
                                @can('flights.search')
                                    <a href="{{ route('flights.index') }}" class="btn btn--blue">
                                        <svg class="ic"><use href="#i-search"/></svg>Search Flights
                                    </a>
                                @endcan
                            @endfeature
                            @feature('account')
                                <a href="{{ route('account.overview') }}" class="btn btn--outline">View Account</a>
                            @endfeature
                        </div>
                    </div>

                    <section class="card" aria-labelledby="dashboard-services-title">
                        <h2 class="card__title" id="dashboard-services-title">Travel services</h2>

                        <div class="dash-services">
                            @feature('flights')
                                @can('flights.search')
                                    <a href="{{ route('flights.index') }}" class="dash-service is-live">
                                        <span class="svc__ic c-blue"><svg class="ic"><use href="#i-plane"/></svg></span>
                                        <b>Flights</b>
                                        <small>Search domestic and international flight options.</small>
                                        <span class="badge badge--success">Available</span>
                                    </a>
                                @else
                                    <article class="dash-service">
                                        <span class="svc__ic c-blue"><svg class="ic"><use href="#i-plane"/></svg></span>
                                        <b>Flights</b>
                                        <small>Flight access is not enabled for this account.</small>
                                        <span class="badge badge--muted">Unavailable</span>
                                    </article>
                                @endcan
                            @endfeature

                            @foreach ([
                                'hotels' => [
                                    'icon' => 'hotel',
                                    'tone' => 'c-orange',
                                    'description' => 'Search configured hotel availability and stay options.',
                                ],
                                'tours' => [
                                    'icon' => 'palm',
                                    'tone' => 'c-green',
                                    'description' => 'Search configured tours and destination activities.',
                                ],
                                'visa' => [
                                    'icon' => 'passport',
                                    'tone' => 'c-indigo',
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
                                        <a href="{{ route($service['route_name']) }}" class="dash-service is-live">
                                            <span class="svc__ic {{ $presentation['tone'] }}"><svg class="ic"><use href="#i-{{ $presentation['icon'] }}"/></svg></span>
                                            <b>{{ $service['label'] }}</b>
                                            <small>{{ $presentation['description'] }}</small>
                                            <span class="badge badge--success">{{ $service['status'] }}</span>
                                        </a>
                                    @else
                                        <article class="dash-service">
                                            <span class="svc__ic {{ $presentation['tone'] }}"><svg class="ic"><use href="#i-{{ $presentation['icon'] }}"/></svg></span>
                                            <b>{{ $service['label'] ?? ucfirst($serviceKey) }}</b>
                                            <small>
                                                @if ($service && $service['available'])
                                                    This service is not enabled for your account.
                                                @else
                                                    This service is not configured for customer use.
                                                @endif
                                            </small>
                                            <span class="badge badge--muted">
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

                    <section class="card" aria-labelledby="dashboard-account-title">
                        <div class="card__title">
                            <h2 class="h3" id="dashboard-account-title">Account snapshot</h2>
                            @feature('account')
                                <a href="{{ route('account.overview') }}" class="link small">Open Account</a>
                            @endfeature
                        </div>

                        <dl class="kv kv--grid">
                            <div><dt>Full Name</dt><dd>{{ auth()->user()->name }}</dd></div>
                            <div><dt>Email Address</dt><dd>{{ auth()->user()->email }}</dd></div>
                            <div><dt>Email Status</dt><dd><span class="badge badge--success">Verified</span></dd></div>
                            <div><dt>Member Since</dt><dd>{{ auth()->user()->created_at?->format('M Y') ?? 'Not available' }}</dd></div>
                        </dl>
                    </section>
                </div>
            </div>
        </div>
    </section>

    @endif

@endsection
