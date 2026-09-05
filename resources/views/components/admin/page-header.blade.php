@props([
    'title',
    'description' => null,
    'icon' => null,
    'eyebrow' => 'Administration',
])

@php
    $routeName = request()->route()?->getName() ?? '';

    $iconKey = match (true) {
        $routeName === 'dashboard' => 'dashboard',

        str_starts_with($routeName, 'admin.users.')
            => 'users',

        str_starts_with($routeName, 'admin.roles.')
            => 'roles',

        str_starts_with($routeName, 'admin.features.')
            => 'features',

        str_starts_with($routeName, 'admin.settings.')
            => 'settings',

        str_starts_with($routeName, 'admin.categories.')
            => 'categories',

        str_starts_with($routeName, 'admin.currencies.')
            => 'currencies',

        str_starts_with($routeName, 'admin.master-data.')
            => 'countries',

        str_starts_with($routeName, 'admin.languages.')
            => 'languages',

        str_starts_with($routeName, 'admin.bookings.')
            => 'bookings',

        str_starts_with($routeName, 'admin.agents.')
            => 'agents',

        str_starts_with($routeName, 'admin.affiliates.')
            => 'affiliates',

        str_starts_with($routeName, 'admin.students.')
            => 'students',

        str_starts_with($routeName, 'admin.institutions.')
            => 'institutions',

        str_starts_with($routeName, 'admin.reports.')
            => 'reports',

        str_starts_with($routeName, 'admin.system-logs.')
            => 'logs',

        default => 'default',
    };
@endphp

<header class="admin-page-heading">
    <div class="admin-page-title-wrap">
        <span class="admin-page-title-icon" aria-hidden="true">

            @switch($iconKey)

                @case('dashboard')
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="2"/>
                        <rect x="14" y="3" width="7" height="7" rx="2"/>
                        <rect x="3" y="14" width="7" height="7" rx="2"/>
                        <rect x="14" y="14" width="7" height="7" rx="2"/>
                    </svg>
                    @break

                @case('users')
                    <svg viewBox="0 0 24 24">
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M2.5 21c.7-4.3 2.9-6.5 6.5-6.5s5.8 2.2 6.5 6.5"/>
                        <path d="M16 4.5a3.5 3.5 0 0 1 0 6.8M17 14.5c2.6.5 4.2 2.5 4.6 5.5"/>
                    </svg>
                    @break

                @case('roles')
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3 4.5 6.5v5.2c0 4.7 3 7.8 7.5 9.3 4.5-1.5 7.5-4.6 7.5-9.3V6.5L12 3Z"/>
                        <path d="m8.5 12 2.2 2.2 4.8-5"/>
                    </svg>
                    @break

                @case('features')
                    <svg viewBox="0 0 24 24">
                        <path d="M4 6h16M4 12h16M4 18h16"/>
                        <circle cx="9" cy="6" r="2"/>
                        <circle cx="15" cy="12" r="2"/>
                        <circle cx="8" cy="18" r="2"/>
                    </svg>
                    @break

                @case('settings')
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19 13.5a7.6 7.6 0 0 0 0-3l2-1.5-2-3.5-2.5 1a8 8 0 0 0-2.5-1.4L13.7 3h-4l-.3 2.1A8 8 0 0 0 7 6.5l-2.5-1L2.5 9l2 1.5a7.6 7.6 0 0 0 0 3l-2 1.5 2 3.5 2.5-1a8 8 0 0 0 2.4 1.4l.3 2.1h4l.3-2.1a8 8 0 0 0 2.5-1.4l2.5 1 2-3.5-2-1.5Z"/>
                    </svg>
                    @break

                @case('categories')
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="4" width="7" height="7" rx="2"/>
                        <rect x="14" y="4" width="7" height="7" rx="2"/>
                        <rect x="3" y="15" width="7" height="6" rx="2"/>
                        <rect x="14" y="15" width="7" height="6" rx="2"/>
                    </svg>
                    @break

                @case('currencies')
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M15.7 8.4c-.9-.8-2.1-1.2-3.5-1.2-1.9 0-3.3.9-3.3 2.3 0 3.7 6.5 1.8 6.5 5.2 0 1.5-1.4 2.5-3.6 2.5-1.5 0-2.9-.5-3.9-1.4M12 5.3v13.4"/>
                    </svg>
                    @break

                @case('countries')
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M3 12h18M12 3c2.7 2.6 4 5.7 4 9s-1.3 6.4-4 9c-2.7-2.6-4-5.7-4-9s1.3-6.4 4-9Z"/>
                    </svg>
                    @break

                @case('languages')
                    <svg viewBox="0 0 24 24">
                        <path d="M4 5h16v12H9l-5 4V5Z"/>
                        <path d="M8 9h8M8 13h5"/>
                    </svg>
                    @break

                @case('bookings')
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="5" width="18" height="16" rx="2"/>
                        <path d="M8 3v4M16 3v4M3 10h18"/>
                        <path d="m8 15 2 2 5-5"/>
                    </svg>
                    @break

                @case('agents')
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M4 21c.7-4.7 3.4-7 8-7s7.3 2.3 8 7"/>
                        <path d="M17.5 5.5 20 8l-2.5 2.5"/>
                    </svg>
                    @break

                @case('affiliates')
                    <svg viewBox="0 0 24 24">
                        <circle cx="6" cy="12" r="3"/>
                        <circle cx="18" cy="6" r="3"/>
                        <circle cx="18" cy="18" r="3"/>
                        <path d="m8.7 10.6 6.5-3.2M8.7 13.4l6.5 3.2"/>
                    </svg>
                    @break

                @case('students')
                    <svg viewBox="0 0 24 24">
                        <path d="m3 9 9-5 9 5-9 5-9-5Z"/>
                        <path d="M6 11.5V16c3.5 3 8.5 3 12 0v-4.5"/>
                    </svg>
                    @break

                @case('institutions')
                    <svg viewBox="0 0 24 24">
                        <path d="M3 21h18M5 21V9l7-5 7 5v12"/>
                        <path d="M9 21v-6h6v6M9 10h.01M15 10h.01"/>
                    </svg>
                    @break

                @case('reports')
                    <svg viewBox="0 0 24 24">
                        <path d="M4 20V11M10 20V5M16 20v-7M22 20H2"/>
                        <path d="m5 7 5-3 5 4 5-4"/>
                    </svg>
                    @break

                @case('logs')
                    <svg viewBox="0 0 24 24">
                        <rect x="4" y="3" width="16" height="18" rx="2"/>
                        <path d="M8 8h8M8 12h8M8 16h5"/>
                    </svg>
                    @break

                @default
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3v4M12 17v4M3 12h4M17 12h4"/>
                        <path d="m5.6 5.6 2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
            @endswitch

        </span>

        <div>
            <span class="admin-page-eyebrow">{{ $eyebrow }}</span>
            <h1>{{ $title }}</h1>

            @if ($description)
                <p>{{ $description }}</p>
            @endif
        </div>
    </div>

    <div class="admin-page-heading-side">
        <nav class="admin-page-breadcrumb" aria-label="Breadcrumb">
            @feature('dashboard')
                <a href="{{ route('dashboard') }}">Dashboard</a>
            @else
                <a href="{{ route('home') }}">Website</a>
            @endfeature

            <span aria-hidden="true">&rsaquo;</span>
            <strong>{{ $title }}</strong>
        </nav>

        @if ($slot->isNotEmpty())
            <div class="admin-page-actions">
                {{ $slot }}
            </div>
        @endif
    </div>
</header>