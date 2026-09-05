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
        .egh-admin {
            margin: 0;
            min-height: 100vh;
            background: #f5f7fb;
            color: #172944;
            font-family: "Instrument Sans", Arial, sans-serif;
        }

        .egh-skip-link {
            position: fixed;
            z-index: 1200;
            top: 10px;
            left: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            background: #ffffff;
            color: #0f2b4d;
            font-weight: 800;
            text-decoration: none;
            transform: translateY(-150%);
        }

        .egh-skip-link:focus {
            transform: translateY(0);
        }

        .egh-shell {
            display: flex;
            min-height: 100vh;
        }

        .egh-side {
            position: sticky;
            z-index: 1000;
            top: 0;
            width: 264px;
            height: 100vh;
            flex: 0 0 264px;
            overflow-y: auto;
            box-sizing: border-box;
            padding: 22px 16px;
            color: #dce7f5;
            background:
                linear-gradient(180deg, #0d2a4d 0%, #081b33 100%);
            box-shadow: 8px 0 28px rgba(13, 42, 77, .08);
        }

        .egh-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            margin: 0 4px 24px;
            color: #ffffff;
            text-decoration: none;
        }

        .egh-brand-mark {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 38px;
            border-radius: 12px;
            background: #2271e0;
            box-shadow: 0 8px 20px rgba(34, 113, 224, .28);
            font-size: 18px;
        }

        .egh-brand-copy strong,
        .egh-brand-copy small {
            display: block;
        }

        .egh-brand-copy strong {
            color: #ffffff;
            font-size: 14px;
            line-height: 1.25;
        }

        .egh-brand-copy small {
            margin-top: 2px;
            color: #9eb4cf;
            font-size: 10px;
        }

        .egh-nav {
            display: grid;
            gap: 5px;
        }

        .egh-nav a {
            display: flex;
            align-items: center;
            min-height: 40px;
            box-sizing: border-box;
            padding: 9px 12px;
            border-radius: 9px;
            color: #b9c9dc;
            font-size: 13px;
            font-weight: 650;
            line-height: 1.3;
            text-decoration: none;
            transition: background .16s ease, color .16s ease;
        }

        .egh-nav a:hover,
        .egh-nav a:focus-visible {
            color: #ffffff;
            background: rgba(255,255,255,.09);
            outline: none;
        }

        .egh-nav a.active {
            color: #ffffff;
            background: #315ff4;
            box-shadow: 0 8px 18px rgba(49, 95, 244, .24);
        }

        .egh-main {
            min-width: 0;
            flex: 1;
        }

        .egh-top {
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            box-sizing: border-box;
            padding: 12px 28px;
            border-bottom: 1px solid #e3e9f2;
            background: rgba(255,255,255,.96);
        }

        .egh-menu-toggle {
            display: none;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            background: #ffffff;
            color: #183456;
            cursor: pointer;
            font-size: 20px;
        }

        .egh-top-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 14px;
            min-width: 0;
            margin-left: auto;
        }

        .egh-user {
            min-width: 0;
            color: #4b6078;
            font-size: 12px;
            text-align: right;
        }

        .egh-user strong {
            display: block;
            overflow: hidden;
            color: #172944;
            font-size: 13px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .egh-top-link,
        .egh-logout {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            padding: 8px 12px;
            border: 1px solid #dbe3ef;
            border-radius: 9px;
            background: #ffffff;
            color: #26415f;
            cursor: pointer;
            font: inherit;
            font-size: 12px;
            font-weight: 750;
            text-decoration: none;
        }

        .egh-content {
            min-width: 0;
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
            box-shadow: 0 6px 24px rgba(21, 45, 82, .04);
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
            margin-bottom: 18px;
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
            font-weight: 500;
        }

        .egh-content table {
            max-width: 100%;
        }

        .egh-content td,
        .egh-content th {
            overflow-wrap: anywhere;
        }

        .egh-backdrop {
            display: none;
        }

        @media (max-width: 900px) {
            .egh-side {
                position: fixed;
                left: 0;
                width: min(300px, calc(100vw - 52px));
                height: 100dvh;
                flex-basis: auto;
                transform: translateX(-105%);
                transition: transform .2s ease;
            }

            .egh-side.is-open {
                transform: translateX(0);
            }

            .egh-menu-toggle {
                display: inline-flex;
                flex: 0 0 auto;
            }

            .egh-backdrop {
                position: fixed;
                z-index: 900;
                inset: 0;
                display: block;
                visibility: hidden;
                background: rgba(4, 18, 35, .48);
                opacity: 0;
                transition: opacity .2s ease, visibility .2s ease;
            }

            .egh-backdrop.is-open {
                visibility: visible;
                opacity: 1;
            }

            .egh-main {
                width: 100%;
            }

            .egh-top {
                min-height: 62px;
                padding: 10px 14px;
            }

            .egh-top-link {
                display: none;
            }

            .egh-user {
                max-width: 170px;
            }

            .egh-content {
                width: 100%;
                padding: 16px;
            }

            .egh-card {
                padding: 16px;
            }

            .egh-content table {
                min-width: 560px;
            }

            body.egh-menu-open {
                overflow: hidden;
            }
        }

        @media (max-width: 560px) {
            .egh-user {
                max-width: 135px;
            }

            .egh-user span {
                display: none;
            }

            .egh-logout {
                padding-inline: 10px;
            }

            .egh-content h1 {
                font-size: 24px;
                line-height: 1.2;
            }
        }
    </style>
</head>

<body class="egh-admin">
<a href="#admin-main" class="egh-skip-link">Skip to admin content</a>

<div class="egh-shell">
    <aside class="egh-side" data-admin-sidebar>
        <a href="{{ route('dashboard') }}" class="egh-brand">
            <span class="egh-brand-mark" aria-hidden="true">âœˆ</span>
            <span class="egh-brand-copy">
                <strong>Eagle Global Hub LTD</strong>
                <small>Admin workspace</small>
            </span>
        </a>

        <nav class="egh-nav" aria-label="Admin navigation">
            <a
                href="{{ route('dashboard') }}"
                class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"
            >Dashboard</a>

            <a
                href="{{ route('admin.bookings.index') }}"
                class="{{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}"
            >Bookings</a>

            @can('reports.view')
                <a
                    href="{{ route('admin.reports.index') }}"
                    class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
                >Reports</a>
            @endcan

            @can('agents.view')
                <a
                    href="{{ route('admin.agents.index') }}"
                    class="{{ request()->routeIs('admin.agents.*') ? 'active' : '' }}"
                >Agents</a>
            @endcan

            @can('affiliates.view')
                <a
                    href="{{ route('admin.affiliates.index') }}"
                    class="{{ request()->routeIs('admin.affiliates.*') ? 'active' : '' }}"
                >Affiliates</a>
            @endcan

            @can('students.view')
                <a
                    href="{{ route('admin.students.index') }}"
                    class="{{ request()->routeIs('admin.students.*') ? 'active' : '' }}"
                >Students</a>
            @endcan

            @can('institutions.view')
                <a
                    href="{{ route('admin.institutions.index') }}"
                    class="{{ request()->routeIs('admin.institutions.*') ? 'active' : '' }}"
                >Institutions</a>
            @endcan

            @can('users.view')
                <a
                    href="{{ route('admin.users.index') }}"
                    class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                >Users</a>
            @endcan

            @can('roles.view')
                <a
                    href="{{ route('admin.roles.index') }}"
                    class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                >Roles &amp; Permissions</a>
            @endcan

            @can('master-data.view')
                <a
                    href="{{ route('admin.master-data.manage') }}"
                    class="{{ request()->routeIs('admin.master-data.*') ? 'active' : '' }}"
                >Master Data</a>

                <a
                    href="{{ route('admin.categories.manage') }}"
                    class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                >Categories</a>

                <a
                    href="{{ route('admin.currencies.manage') }}"
                    class="{{ request()->routeIs('admin.currencies.*') ? 'active' : '' }}"
                >Currencies</a>

                <a
                    href="{{ route('admin.languages.manage') }}"
                    class="{{ request()->routeIs('admin.languages.*') ? 'active' : '' }}"
                >Languages</a>
            @endcan

            @role('super-admin')
                <a
                    href="{{ route('admin.features.index') }}"
                    class="{{ request()->routeIs('admin.features.*') ? 'active' : '' }}"
                >Feature Control</a>
            @endrole

            @can('settings.view')
                <a
                    href="{{ route('admin.settings.manage') }}"
                    class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
                >Settings</a>
            @endcan

            @can('system-logs.view')
                <a
                    href="{{ route('admin.system-logs.index') }}"
                    class="{{ request()->routeIs('admin.system-logs.*') ? 'active' : '' }}"
                >System Logs</a>
            @endcan
        </nav>
    </aside>

    <div class="egh-backdrop" data-admin-backdrop aria-hidden="true"></div>

    <div class="egh-main">
        <header class="egh-top">
            <button
                type="button"
                class="egh-menu-toggle"
                data-admin-menu-toggle
                aria-controls="admin-navigation"
                aria-expanded="false"
                aria-label="Open admin navigation"
            >â˜°</button>

            <div class="egh-top-actions">
                <div class="egh-user">
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>{{ auth()->user()->getRoleNames()->join(', ') }}</span>
                </div>

                <a href="{{ route('home') }}" class="egh-top-link">View site</a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="egh-logout">Logout</button>
                </form>
            </div>
        </header>

        <main class="egh-content" id="admin-main">
            @if (session('status'))
                <div class="egh-alert" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const side = document.querySelector('[data-admin-sidebar]');
    const toggle = document.querySelector('[data-admin-menu-toggle]');
    const backdrop = document.querySelector('[data-admin-backdrop]');

    if (!side || !toggle || !backdrop) {
        return;
    }

    const setOpen = function (open) {
        side.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-open', open);
        document.body.classList.toggle('egh-menu-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute(
            'aria-label',
            open ? 'Close admin navigation' : 'Open admin navigation'
        );
    };

    toggle.addEventListener('click', function () {
        setOpen(!side.classList.contains('is-open'));
    });

    backdrop.addEventListener('click', function () {
        setOpen(false);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
});
</script>
</body>
</html>
