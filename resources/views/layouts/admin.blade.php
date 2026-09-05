<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin') | Eagle Global Hub LTD</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /*
        |--------------------------------------------------------------------------
        | Compatibility utilities for existing Admin pages
        |--------------------------------------------------------------------------
        */

        .egh-content {
            min-width: 0;
            width: 100%;
            box-sizing: border-box;
            padding: 28px;
        }

        .egh-content > * {
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }

        .egh-card {
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
            overflow-x: auto;
            padding: 20px;
            border: 1px solid #e1e7f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 6px 24px rgba(21,45,82,.04);
        }

        .egh-button {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            padding: 9px 14px;
            border: 0;
            border-radius: 9px;
            background: #315ff4;
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-size: 13px;
            font-weight: 750;
            text-decoration: none;
        }

        .egh-button.secondary {
            border: 1px solid #dbe3ef;
            background: #eef2f7;
            color: #273852;
        }

        .egh-alert {
            margin: 0 28px 18px;
            padding: 12px 14px;
            border: 1px solid #ccebd8;
            border-radius: 10px;
            background: #eefaf2;
            color: #22643b;
        }

        .egh-content input:not([type="checkbox"]):not([type="radio"]),
        .egh-content select,
        .egh-content textarea {
            max-width: 100%;
            box-sizing: border-box;
        }

        .egh-content form.egh-card > p > label {
            display: block;
            max-width: 640px;
            color: #263d58;
            font-weight: 700;
        }

        .egh-content form.egh-card > p input:not([type="checkbox"]):not([type="radio"]),
        .egh-content form.egh-card > p select,
        .egh-content form.egh-card > p textarea {
            width: 100%;
            min-height: 42px;
            margin-top: 7px;
            padding: 9px 11px;
            border: 1px solid #cfd9e7;
            border-radius: 9px;
            background: #ffffff;
            color: #172944;
            font: inherit;
        }

        .egh-content table {
            max-width: 100%;
        }

        .egh-content td,
        .egh-content th {
            overflow-wrap: anywhere;
        }

        .admin-footer {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 28px;
            border-top: 1px solid #e5eaf1;
            background: #ffffff;
            color: #7a8798;
            font-size: 12px;
        }

        .egh-skip-link {
            position: fixed;
            z-index: 2000;
            top: 8px;
            left: 8px;
            padding: 9px 12px;
            border-radius: 8px;
            background: #ffffff;
            color: #163657;
            font-weight: 700;
            text-decoration: none;
            transform: translateY(-150%);
        }

        .egh-skip-link:focus {
            transform: translateY(0);
        }

        @media (max-width: 900px) {
            .egh-content {
                padding: 16px;
            }

            .egh-card {
                padding: 16px;
            }

            .egh-content table {
                min-width: 560px;
            }

            .admin-footer {
                padding: 16px;
                flex-direction: column;
            }
        }
    </style>
</head>

@php
    $adminUser = auth()->user();
    $adminRole = $adminUser?->getRoleNames()->first();
    $adminRoleLabel = $adminRole
        ? ucwords(str_replace('-', ' ', $adminRole))
        : 'Admin';
@endphp

<body class="admin-body">

<a href="#admin-main" class="egh-skip-link">
    Skip to admin content
</a>

<div class="admin-shell">

    <aside
        class="admin-sidebar"
        id="adminSidebar"
        aria-label="Administration"
    >
        <div class="admin-brand">
            <div class="admin-brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M21.5 15.5 13 12l3.5-8.5L14 2l-5.5 8.5L3 8.5 1.5 10 6 14l-2 6 1.5 1 4-5 5 4 1.5-1-3-5.5 8.5 3.5z"/>
                </svg>
            </div>

            <div class="admin-brand-copy">
                <strong>Eagle Global Hub LTD</strong>
                <span>Smart Travel Booking</span>
            </div>
        </div>

        <nav class="admin-nav">

            <div class="admin-nav-section">
                <span class="admin-nav-title">MAIN</span>

                @feature('dashboard')
                    <a
                        href="{{ route('dashboard') }}"
                        class="admin-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 10.8 12 3l9 7.8V21h-6v-6H9v6H3z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                @endfeature

                @can('users.view')
                    <a
                        href="{{ route('admin.users.index') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="9" cy="8" r="4"/>
                            <path d="M2.5 20c.8-4 3-6 6.5-6s5.7 2 6.5 6M16 7a3 3 0 1 1 0 6M17 14c2.6.4 4.2 2.2 4.7 5"/>
                        </svg>
                        <span>Users</span>
                    </a>
                @endcan

                @can('roles.view')
                    <a
                        href="{{ route('admin.roles.index') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7z"/>
                            <path d="m8.5 12 2.2 2.2 4.8-5"/>
                        </svg>
                        <span>Roles &amp; Permissions</span>
                    </a>
                @endcan
            </div>

            <div class="admin-nav-section">
                <span class="admin-nav-title">SYSTEM</span>

                @role('super-admin')
                    <a
                        href="{{ route('admin.features.index') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.features.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h16"/>
                            <circle cx="9" cy="7" r="2"/>
                            <circle cx="15" cy="12" r="2"/>
                            <circle cx="8" cy="17" r="2"/>
                        </svg>
                        <span>Feature Control</span>
                    </a>
                @endrole

                @can('settings.view')
                    <a
                        href="{{ route('admin.settings.manage') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21H10v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H3v-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3h4v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1v4H21a1.7 1.7 0 0 0-1.6 1z"/>
                        </svg>
                        <span>Settings</span>
                    </a>
                @endcan

                @can('master-data.view')
                    <a
                        href="{{ route('admin.categories.manage') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <path d="M8 4v16M3 10h18"/>
                        </svg>
                        <span>Categories</span>
                    </a>

                    <a
                        href="{{ route('admin.currencies.manage') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.currencies.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M15.5 8.5c-.8-.7-1.9-1-3.2-1-1.8 0-3.2.8-3.2 2.1 0 3.4 6.8 1.7 6.8 5.2 0 1.3-1.4 2.2-3.4 2.2-1.5 0-2.8-.4-3.8-1.3M12 5v14"/>
                        </svg>
                        <span>Currencies</span>
                    </a>

                    <a
                        href="{{ route('admin.master-data.manage') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.master-data.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M3 12h18M12 3c2.8 2.7 4 5.7 4 9s-1.2 6.3-4 9c-2.8-2.7-4-5.7-4-9s1.2-6.3 4-9z"/>
                        </svg>
                        <span>Countries</span>
                    </a>

                    <a
                        href="{{ route('admin.languages.manage') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.languages.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 5h16v12H8l-4 4z"/>
                            <path d="M8 9h8M8 13h5"/>
                        </svg>
                        <span>Languages</span>
                    </a>
                @endcan
            </div>

            <div class="admin-nav-section">
                <span class="admin-nav-title">BUSINESS</span>

                <a
                    href="{{ route('admin.bookings.index') }}"
                    class="admin-nav-link {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="16" rx="2"/>
                        <path d="M8 3v4M16 3v4M3 10h18"/>
                    </svg>
                    <span>Bookings</span>
                </a>

                @can('agents.view')
                    <a
                        href="{{ route('admin.agents.index') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.agents.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="7" r="4"/>
                            <path d="M4 21c.7-4.7 3.4-7 8-7s7.3 2.3 8 7"/>
                        </svg>
                        <span>Agents</span>
                    </a>
                @endcan

                @can('affiliates.view')
                    <a
                        href="{{ route('admin.affiliates.index') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.affiliates.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="6" cy="12" r="3"/>
                            <circle cx="18" cy="6" r="3"/>
                            <circle cx="18" cy="18" r="3"/>
                            <path d="m8.8 10.6 6.4-3.2M8.8 13.4l6.4 3.2"/>
                        </svg>
                        <span>Affiliates</span>
                    </a>
                @endcan

                @can('students.view')
                    <a
                        href="{{ route('admin.students.index') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m3 9 9-5 9 5-9 5z"/>
                            <path d="M6 11v5c3.5 3 8.5 3 12 0v-5"/>
                        </svg>
                        <span>Students</span>
                    </a>
                @endcan

                @can('institutions.view')
                    <a
                        href="{{ route('admin.institutions.index') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.institutions.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 21h18M5 21V9l7-5 7 5v12"/>
                            <path d="M9 21v-6h6v6"/>
                        </svg>
                        <span>Institutions</span>
                    </a>
                @endcan

                @can('reports.view')
                    <a
                        href="{{ route('admin.reports.index') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
                        </svg>
                        <span>Reports</span>
                    </a>
                @endcan
            </div>

            @can('system-logs.view')
                <div class="admin-nav-section">
                    <span class="admin-nav-title">SYSTEM INFO</span>

                    <a
                        href="{{ route('admin.system-logs.index') }}"
                        class="admin-nav-link {{ request()->routeIs('admin.system-logs.*') ? 'active' : '' }}"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="4" y="3" width="16" height="18" rx="2"/>
                            <path d="M8 8h8M8 12h8M8 16h5"/>
                        </svg>
                        <span>System Logs</span>
                    </a>
                </div>
            @endcan
        </nav>
    </aside>

    <div class="admin-main">

        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button
                    type="button"
                    class="admin-menu-button"
                    id="adminMenuButton"
                    aria-controls="adminSidebar"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

            <div class="admin-topbar-actions">
                <div class="admin-user">
                    <div class="admin-user-avatar">
                        {{ strtoupper(substr($adminUser?->name ?? 'A', 0, 1)) }}
                    </div>

                    <div class="admin-user-copy">
                        <strong>{{ $adminUser?->name ?? 'Admin User' }}</strong>
                        <span>{{ $adminRoleLabel }}</span>
                    </div>
                </div>

                <a class="admin-topbar-link admin-topbar-site-link" href="{{ route('home') }}">View site</a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="admin-topbar-link" type="submit">Sign out</button>
                </form>
            </div>
        </header>

        <main
            id="admin-main"
            class="@yield('page-class', 'admin-page')"
        >
            @if (
                session('status')
                && ! request()->routeIs('admin.settings.*')
                && ! request()->routeIs('admin.features.*')
            )
                <div class="egh-alert" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="admin-footer">
            <span>
                &copy; {{ date('Y') }} Eagle Global Hub LTD. All rights reserved.
            </span>

            <span>
                Version 1.0.0
            </span>
        </footer>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuButton = document.getElementById('adminMenuButton');
    const sidebar = document.getElementById('adminSidebar');

    if (!menuButton || !sidebar) {
        return;
    }

    const setOpen = function (open) {
        sidebar.classList.toggle('is-open', open);
        menuButton.setAttribute(
            'aria-expanded',
            open ? 'true' : 'false'
        );
    };

    menuButton.addEventListener('click', function () {
        setOpen(!sidebar.classList.contains('is-open'));
    });

    sidebar
        .querySelectorAll('.admin-nav-link')
        .forEach(function (link) {
            link.addEventListener('click', function () {
                setOpen(false);
            });
        });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
});
</script>

@yield('scripts')

</body>
</html>
