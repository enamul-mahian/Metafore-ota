<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Settings | Eagle Global Hub LTD</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="admin-body">

@php
    $user = auth()->user();
    $canManageSettings = $user?->can('settings.manage') ?? false;
@endphp

<div class="admin-shell">

    {{-- =========================================================
        SIDEBAR
    ========================================================== --}}
    <aside class="admin-sidebar" id="adminSidebar">

        <div class="admin-brand">

            <div class="admin-brand-mark">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21.5 15.5 13 12l3.5-8.5L14 2l-5.5 8.5L3 8.5 1.5 10 6 14l-2 6 1.5 1 4-5 5 4 1.5-1-3-5.5 8.5 3.5z"/>
                </svg>
            </div>

            <div class="admin-brand-copy">
                <strong>Eagle Global Hub LTD</strong>
                <span>Smart Travel Booking</span>
            </div>

        </div>

        <nav class="admin-nav">

            {{-- MAIN --}}
            <div class="admin-nav-section">

                <span class="admin-nav-title">MAIN</span>

                @feature('dashboard')
                    <a href="{{ route('dashboard') }}" class="admin-nav-link">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 10.8 12 3l9 7.8V21h-6v-6H9v6H3z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                @endfeature

                <a href="{{ route('admin.users.index') }}" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="9" cy="8" r="4"/>
                        <path d="M2.5 20c.8-4 3-6 6.5-6s5.7 2 6.5 6M16 7a3 3 0 1 1 0 6M17 14c2.6.4 4.2 2.2 4.7 5"/>
                    </svg>
                    <span>Users</span>
                </a>

                <a href="{{ route('admin.roles.index') }}" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7z"/>
                        <path d="m8.5 12 2.2 2.2 4.8-5"/>
                    </svg>
                    <span>Roles &amp; Permissions</span>
                </a>

            </div>

            {{-- SYSTEM --}}
            <div class="admin-nav-section">

                <span class="admin-nav-title">SYSTEM</span>

                @if ($user?->hasRole('super-admin'))
                    <a
                        href="{{ route('admin.features.index') }}"
                        class="admin-nav-link"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 7h16M4 12h16M4 17h16"/>
                            <circle cx="9" cy="7" r="2"/>
                            <circle cx="15" cy="12" r="2"/>
                            <circle cx="8" cy="17" r="2"/>
                        </svg>
                        <span>Feature Control</span>
                    </a>
                @endif

                <a
                    href="{{ route('admin.settings.manage') }}"
                    class="admin-nav-link active"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21H10v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H3v-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3h4v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1v4H21a1.7 1.7 0 0 0-1.6 1z"/>
                    </svg>
                    <span>Settings</span>
                </a>

                <a href="{{ route('admin.categories.manage') }}" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                        <path d="M8 4v16M3 10h18"/>
                    </svg>
                    <span>Categories</span>
                </a>

                <a href="{{ route('admin.currencies.manage') }}" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M15.5 8.5c-.8-.7-1.9-1-3.2-1-1.8 0-3.2.8-3.2 2.1 0 3.4 6.8 1.7 6.8 5.2 0 1.3-1.4 2.2-3.4 2.2-1.5 0-2.8-.4-3.8-1.3M12 5v14"/>
                    </svg>
                    <span>Currencies</span>
                </a>

                <a href="{{ route('admin.master-data.manage') }}" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M3 12h18M12 3c2.8 2.7 4 5.7 4 9s-1.2 6.3-4 9c-2.8-2.7-4-5.7-4-9s1.2-6.3 4-9z"/>
                    </svg>
                    <span>Countries</span>
                </a>

                <a href="{{ route('admin.languages.manage') }}" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 5h16v12H8l-4 4z"/>
                        <path d="M8 9h8M8 13h5"/>
                    </svg>
                    <span>Languages</span>
                </a>

            </div>

            {{-- BUSINESS --}}
            <div class="admin-nav-section">

                <span class="admin-nav-title">BUSINESS</span>

                <a href="{{ route('admin.bookings.index') }}" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="16" rx="2"/>
                        <path d="M8 3v4M16 3v4M3 10h18"/>
                    </svg>
                    <span>Bookings</span>
                </a>

                @can('agents.view')
                <a href="{{ route('admin.agents.index') }}" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M4 21c.7-4.7 3.4-7 8-7s7.3 2.3 8 7"/>
                    </svg>
                    <span>Agents</span>
                </a>
                @endcan

                @can('affiliates.view')
                <a href="{{ route('admin.affiliates.index') }}" class="admin-nav-link">
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
                <a href="{{ route('admin.students.index') }}" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="m3 9 9-5 9 5-9 5z"/>
                        <path d="M6 11v5c3.5 3 8.5 3 12 0v-5"/>
                    </svg>
                    <span>Students</span>
                </a>
                @endcan

                @can('institutions.view')
                <a href="{{ route('admin.institutions.index') }}" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 21h18M5 21V9l7-5 7 5v12"/>
                        <path d="M9 21v-6h6v6"/>
                    </svg>
                    <span>Institutions</span>
                </a>
                @endcan

                @can('reports.view')
                <a href="{{ route('admin.reports.index') }}" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
                    </svg>
                    <span>Reports</span>
                </a>
                @endcan

            </div>

            {{-- SYSTEM INFO --}}
            <div class="admin-nav-section">

                <span class="admin-nav-title">SYSTEM INFO</span>

                <a href="#" class="admin-nav-link">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="4" y="3" width="16" height="18" rx="2"/>
                        <path d="M8 8h8M8 12h8M8 16h5"/>
                    </svg>
                    <span>System Logs</span>
                </a>

            </div>

        </nav>

    </aside>

    {{-- =========================================================
        MAIN
    ========================================================== --}}
    <div class="admin-main">

        {{-- TOPBAR --}}
        <header class="admin-topbar">

            <div class="admin-topbar-left">

                <button
                    type="button"
                    class="admin-menu-button"
                    id="adminMenuButton"
                    aria-label="Toggle navigation"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div class="admin-search">

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-4-4"/>
                    </svg>

                    <input
                        type="search"
                        placeholder="Search anything..."
                        aria-label="Search"
                    >

                </div>

            </div>

            <div class="admin-topbar-actions">

                <button
                    class="admin-icon-button"
                    type="button"
                    aria-label="Theme"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20 15.5A8 8 0 0 1 8.5 4 8.5 8.5 0 1 0 20 15.5z"/>
                    </svg>
                </button>

                <button
                    class="admin-icon-button notification-button"
                    type="button"
                    aria-label="Notifications"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>
                    </svg>

                    <span class="notification-count">3</span>
                </button>

                <div class="admin-user">

                    <div class="admin-user-avatar">
                        {{ strtoupper(substr($user?->name ?? 'A', 0, 1)) }}
                    </div>

                    <div class="admin-user-copy">

                        <strong>
                            {{ $user?->name ?? 'Admin User' }}
                        </strong>

                        <span>
                            {{ $canManageSettings ? 'Super Admin' : 'Admin' }}
                        </span>

                    </div>

                    <svg
                        class="admin-user-chevron"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path d="m7 10 5 5 5-5"/>
                    </svg>

                </div>

            </div>

        </header>

        <main class="settings-page">

            {{-- =====================================================
                PAGE HEADING
            ====================================================== --}}
            <div class="settings-page-heading">

                <div class="settings-title-wrap">

                    <div class="settings-title-icon">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21H10v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H3v-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.6V3h4v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1v4H21a1.7 1.7 0 0 0-1.6 1z"/>
                        </svg>

                    </div>

                    <div>

                        <h1>Settings</h1>

                        @unless($canManageSettings)

                            <span class="settings-access-mode">
                                Read only mode
                            </span>

                        @endunless

                    </div>

                </div>

                <div class="settings-breadcrumb">
                    @feature('dashboard')
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    @else
                        <a href="{{ route('home') }}">Website</a>
                    @endfeature
                    <span>â€º</span>
                    <strong>Settings</strong>
                </div>

            </div>

            {{-- =====================================================
                LIVE SUMMARY CARDS
            ====================================================== --}}
            <section class="settings-stat-grid">

                {{-- TOTAL --}}
                <article class="settings-stat-card">

                    <div class="settings-stat-icon blue">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 6h16M4 12h16M4 18h16"/>
                            <circle cx="9" cy="6" r="2"/>
                            <circle cx="15" cy="12" r="2"/>
                            <circle cx="8" cy="18" r="2"/>
                        </svg>

                    </div>

                    <div>
                        <span>Total Settings</span>
                        <strong>{{ $totalSettings }}</strong>
                        <small>All system settings</small>
                    </div>

                </article>

                {{-- PUBLIC --}}
                <article class="settings-stat-card">

                    <div class="settings-stat-icon green">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M3 12h18M12 3c2.8 2.7 4 5.7 4 9s-1.2 6.3-4 9c-2.8-2.7-4-5.7-4-9s1.2-6.3 4-9z"/>
                        </svg>

                    </div>

                    <div>
                        <span>Public Settings</span>
                        <strong>{{ $publicSettings }}</strong>
                        <small>Visible on website</small>
                    </div>

                </article>

                {{-- PRIVATE --}}
                <article class="settings-stat-card">

                    <div class="settings-stat-icon orange">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="5" y="10" width="14" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>

                    </div>

                    <div>
                        <span>Private Settings</span>
                        <strong>{{ $privateSettings }}</strong>
                        <small>Private / Admin only</small>
                    </div>

                </article>

                {{-- GROUP COUNT --}}
                <article class="settings-stat-card">

                    <div class="settings-stat-icon purple">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 13 13 20 4 11V4h7z"/>
                            <circle cx="8.5" cy="8.5" r="1.2"/>
                        </svg>

                    </div>

                    <div>
                        <span>Setting Groups</span>
                        <strong>{{ $settingGroups }}</strong>
                        <small>Configuration groups</small>
                    </div>

                </article>

            </section>

            {{-- =====================================================
                SETTINGS WORKSPACE
            ====================================================== --}}
            <section class="settings-workspace">

                {{-- =================================================
                    LIVE GROUP PANEL
                ================================================== --}}
                <aside class="settings-group-panel">

                    <div class="settings-panel-heading">
                        <h2>Setting Groups</h2>
                    </div>

                    <div class="settings-group-list">

                        @forelse($groups as $groupName => $groupItems)

                            <a
                                href="{{ route('admin.settings.manage', ['group' => $groupName]) }}"
                                class="settings-group-item {{ $activeGroup === $groupName ? 'active' : '' }}"
                            >

                                <span class="settings-group-icon">

                                    {{-- GENERAL --}}
                                    @if($groupName === 'general')

                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M3 11 12 4l9 7"/>
                                            <path d="M5 10v10h14V10M9 20v-6h6v6"/>
                                        </svg>

                                    {{-- LOCALIZATION --}}
                                    @elseif($groupName === 'localization')

                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9"/>
                                            <path d="M3 12h18"/>
                                            <path d="M12 3c2.8 2.7 4 5.7 4 9s-1.2 6.3-4 9"/>
                                            <path d="M12 3c-2.8 2.7-4 5.7-4 9s1.2 6.3 4 9"/>
                                        </svg>

                                    {{-- CONTACT --}}
                                    @elseif($groupName === 'contact')

                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M6.5 3.5 10 8 8 10c1.6 3.1 3.9 5.4 7 7l2-2 3.5 3.5-1.6 2.2c-.6.8-1.6 1.2-2.6 1-7.4-1.6-12.4-6.6-14-14-.2-1 .2-2 1-2.6z"/>
                                        </svg>

                                    {{-- PAYMENT --}}
                                    @elseif($groupName === 'payment')

                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                                            <path d="M3 10h18M7 15h4"/>
                                        </svg>

                                    {{-- EMAIL --}}
                                    @elseif($groupName === 'email')

                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                                            <path d="m4 7 8 6 8-6"/>
                                        </svg>

                                    {{-- SOCIAL --}}
                                    @elseif($groupName === 'social')

                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="6" cy="12" r="3"/>
                                            <circle cx="18" cy="6" r="3"/>
                                            <circle cx="18" cy="18" r="3"/>
                                            <path d="m8.8 10.6 6.4-3.2M8.8 13.4l6.4 3.2"/>
                                        </svg>

                                    {{-- DEFAULT --}}
                                    @else

                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M12 2v3M12 19v3M4.9 4.9 7 7M17 17l2.1 2.1M2 12h3M19 12h3M4.9 19.1 7 17M17 7l2.1-2.1"/>
                                        </svg>

                                    @endif

                                </span>

                                <span>
                                    {{ str($groupName)->headline() }}
                                </span>

                                <strong>
                                    {{ $groupItems->count() }}
                                </strong>

                            </a>

                        @empty

                            <div class="settings-empty-state">
                                No setting groups found.
                            </div>

                        @endforelse

                    </div>

                    @if($canManageSettings)

                        {{-- Action wiring will be added later --}}
                        <button
                            type="button"
                            class="settings-add-group"
                        >
                            <span>ï¼‹</span>
                            Add New Group
                        </button>

                    @endif

                </aside>

                {{-- =================================================
                    LIVE ACTIVE GROUP DATA PANEL
                ================================================== --}}
                <div class="settings-data-panel">

                    <div class="settings-data-heading">

                        <div>

                            <h2>
                                {{ str($activeGroup)->headline() }} Settings
                            </h2>

                            <p>
                                Manage {{ str($activeGroup)->headline()->lower() }}
                                configuration settings
                            </p>

                        </div>

                        @if($canManageSettings)

                            {{-- Step 5C.3C: Add Setting modal wired to the verified POST endpoint. --}}
                            <button
                                type="button"
                                class="settings-primary-button"
                                data-setting-add
                                data-setting-group="{{ $activeGroup }}"
                            >
                                <span>ï¼‹</span>
                                Add New Setting
                            </button>

                        @endif

                    </div>

                    <div class="settings-table-wrap">

                        <table class="settings-table">

                            <thead>
                            <tr>
                                <th>KEY</th>
                                <th>VALUE</th>
                                <th>TYPE</th>
                                <th>VISIBILITY</th>
                                <th>UPDATED</th>
                                <th>ACTIONS</th>
                            </tr>
                            </thead>

                            {{-- =========================================
                                LIVE DATABASE ROWS
                            ========================================== --}}
                            <tbody>

                            @forelse($activeSettings as $setting)

                                <tr>

                                    {{-- KEY --}}
                                    <td>

                                        <strong>
                                            {{ $setting['key'] }}
                                        </strong>

                                        <small>
                                            {{ str($setting['key'])->replace('_', ' ')->headline() }}
                                        </small>

                                    </td>

                                    {{-- VALUE --}}
                                    <td>

                                        @if($setting['type'] === 'boolean')

                                            <span class="settings-value-badge">
                                                {{ $setting['value'] ? 'Yes' : 'No' }}
                                            </span>

                                        @elseif($setting['type'] === 'json')

                                            <span>
                                                {{ json_encode(
                                                    $setting['value'],
                                                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                                ) }}
                                            </span>

                                        @elseif($setting['value'] === null || $setting['value'] === '')

                                            <span class="settings-muted">
                                                â€”
                                            </span>

                                        @else

                                            {{ $setting['value'] }}

                                        @endif

                                    </td>

                                    {{-- TYPE --}}
                                    <td>

                                        <span
                                            class="settings-type-badge {{ $setting['type'] }}"
                                        >
                                            {{ $setting['type'] }}
                                        </span>

                                    </td>

                                    {{-- VISIBILITY --}}
                                    <td>

                                        @if($setting['is_public'])

                                            <span class="settings-visibility-badge public">
                                                â—‰ Public
                                            </span>

                                        @else

                                            <span class="settings-visibility-badge private">
                                                â—‰ Private
                                            </span>

                                        @endif

                                    </td>

                                    {{-- UPDATED --}}
                                    <td class="settings-muted">

                                        {{ $setting['updated_at']?->diffForHumans() ?? 'â€”' }}

                                    </td>

                                    {{-- ACTIONS --}}
                                    <td>

                                        @if($canManageSettings)

                                            <div class="settings-actions">

                                                {{-- =================================================
                                                    EDIT â€” MODAL UI ONLY
                                                    No database write in Step 5C.1A
                                                ================================================== --}}
                                                <button
                                                    type="button"
                                                    class="settings-action edit"
                                                    data-setting-edit
                                                    data-setting-group="{{ $setting['group'] }}"
                                                    data-setting-key="{{ $setting['key'] }}"
                                                    data-setting-type="{{ $setting['type'] }}"
                                                    data-setting-public="{{ $setting['is_public'] ? '1' : '0' }}"
                                                    data-setting-value='{{ json_encode(
                                                        $setting['value'],
                                                        JSON_HEX_TAG
                                                        | JSON_HEX_APOS
                                                        | JSON_HEX_QUOT
                                                        | JSON_HEX_AMP
                                                        | JSON_UNESCAPED_UNICODE
                                                        | JSON_UNESCAPED_SLASHES
                                                    ) }}'
                                                    aria-label="Edit {{ $setting['key'] }}"
                                                    title="Edit"
                                                >

                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M12 20h9"/>
                                                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                                    </svg>

                                                </button>

                                                {{-- =================================================
                                                    DELETE â€” LIVE ENDPOINT WIRING
                                                    Step 5C.2B
                                                ================================================== --}}
                                                <button
                                                    type="button"
                                                    class="settings-action delete"
                                                    data-setting-delete
                                                    data-setting-group="{{ $setting['group'] }}"
                                                    data-setting-key="{{ $setting['key'] }}"
                                                    aria-label="Delete {{ $setting['key'] }}"
                                                    title="Delete"
                                                >

                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M3 6h18"/>
                                                        <path d="M8 6V4h8v2"/>
                                                        <path d="m19 6-1 14H6L5 6"/>
                                                        <path d="M10 11v5M14 11v5"/>
                                                    </svg>

                                                </button>

                                            </div>

                                        @else

                                            <span class="settings-read-only-label">
                                                View only
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="6">

                                        <div class="settings-empty-state">
                                            No settings found in this group.
                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                            </tbody>

                        </table>

                    </div>

                    {{-- =============================================
                        DYNAMIC TABLE FOOTER
                    ============================================== --}}
                    <div class="settings-table-footer">

                        <span>
                            Showing {{ $activeSettings->count() }}
                            of {{ $activeSettings->count() }} settings
                        </span>

                        <div class="settings-pagination">

                            <button
                                type="button"
                                disabled
                            >
                                â€¹
                            </button>

                            <button
                                type="button"
                                class="active"
                            >
                                1
                            </button>

                            <button
                                type="button"
                                disabled
                            >
                                â€º
                            </button>

                        </div>

                    </div>

                </div>

            </section>

            {{-- =====================================================
                ADD SETTING MODAL â€” STEP 5C.3C
                LIVE POST CREATE ENDPOINT WIRING
            ====================================================== --}}
            @if($canManageSettings)

                <div
                    class="settings-modal"
                    id="settingsAddModal"
                    hidden
                >

                    <div
                        class="settings-modal-backdrop"
                        data-settings-add-modal-close
                    ></div>

                    <div
                        class="settings-modal-dialog"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="settingsAddModalTitle"
                    >

                        {{-- MODAL HEADER --}}
                        <div class="settings-modal-header">

                            <div>

                                <h2 id="settingsAddModalTitle">
                                    Add New Setting
                                </h2>

                                <p>
                                    Prepare a new configuration setting.
                                </p>

                            </div>

                            <button
                                type="button"
                                class="settings-modal-close"
                                data-settings-add-modal-close
                                aria-label="Close add setting modal"
                            >
                                Ã—
                            </button>

                        </div>

                        <form id="addSettingForm">

                            {{-- MODAL BODY --}}
                            <div class="settings-modal-body">

                                {{-- GROUP --}}
                                <div class="settings-form-field">

                                    <label for="addSettingGroup">
                                        Group
                                    </label>

                                    <input
                                        type="text"
                                        id="addSettingGroup"
                                        disabled
                                    >

                                </div>

                                {{-- KEY --}}
                                <div class="settings-form-field">

                                    <label for="addSettingKey">
                                        Key
                                    </label>

                                    <input
                                        type="text"
                                        id="addSettingKey"
                                        autocomplete="off"
                                        placeholder="example_key"
                                    >

                                </div>

                                {{-- TYPE --}}
                                <div class="settings-form-field">

                                    <label for="addSettingType">
                                        Type
                                    </label>

                                    <select id="addSettingType">
                                        <option value="string">String</option>
                                        <option value="integer">Integer</option>
                                        <option value="float">Float</option>
                                        <option value="boolean">Boolean</option>
                                        <option value="json">JSON</option>
                                    </select>

                                </div>

                                {{-- VALUE --}}
                                <div class="settings-form-field">

                                    <label for="addSettingValue">
                                        Value
                                    </label>

                                    <textarea
                                        id="addSettingValue"
                                        rows="4"
                                        autocomplete="off"
                                        placeholder="Setting value"
                                    ></textarea>

                                </div>

                                {{-- PUBLIC --}}
                                <label class="settings-form-check">

                                    <input
                                        type="checkbox"
                                        id="addSettingPublic"
                                    >

                                    <span>
                                        Public setting
                                    </span>

                                </label>

                                <div
                                    id="addSettingError"
                                    class="settings-form-error"
                                    hidden
                                ></div>

                            </div>

                            {{-- MODAL FOOTER --}}
                            <div class="settings-modal-footer">

                                <button
                                    type="button"
                                    class="settings-secondary-button"
                                    data-settings-add-modal-close
                                >
                                    Cancel
                                </button>

                                {{-- Enabled by JS only when a valid group/key is present. --}}
                                <button
                                    type="submit"
                                    class="settings-primary-button"
                                    id="addSettingSaveButton"
                                    disabled
                                >
                                    Add Setting
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            @endif

            {{-- =====================================================
                EDIT SETTING MODAL â€” STEP 5C.1B
                EXISTING PATCH ENDPOINT WIRING
            ====================================================== --}}
            @if($canManageSettings)

                <div
                    class="settings-modal"
                    id="settingsEditModal"
                    hidden
                >

                    <div
                        class="settings-modal-backdrop"
                        data-settings-modal-close
                    ></div>

                    <div
                        class="settings-modal-dialog"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="settingsEditModalTitle"
                    >

                        {{-- MODAL HEADER --}}
                        <div class="settings-modal-header">

                            <div>

                                <h2 id="settingsEditModalTitle">
                                    Edit Setting
                                </h2>

                                <p>
                                    Review and update this configuration value.
                                </p>

                            </div>

                            <button
                                type="button"
                                class="settings-modal-close"
                                data-settings-modal-close
                                aria-label="Close edit setting modal"
                            >
                                Ã—
                            </button>

                        </div>

                        {{-- MODAL BODY --}}
                        <div class="settings-modal-body">

                            {{-- GROUP --}}
                            <div class="settings-form-field">

                                <label for="editSettingGroup">
                                    Group
                                </label>

                                <input
                                    type="text"
                                    id="editSettingGroup"
                                    disabled
                                >

                            </div>

                            {{-- KEY --}}
                            <div class="settings-form-field">

                                <label for="editSettingKey">
                                    Key
                                </label>

                                <input
                                    type="text"
                                    id="editSettingKey"
                                    disabled
                                >

                            </div>

                            {{-- TYPE --}}
                            <div class="settings-form-field">

                                <label for="editSettingType">
                                    Type
                                </label>

                                <input
                                    type="text"
                                    id="editSettingType"
                                    disabled
                                >

                            </div>

                            {{-- VALUE --}}
                            <div class="settings-form-field">

                                <label for="editSettingValue">
                                    Value
                                </label>

                                <textarea
                                    id="editSettingValue"
                                    rows="4"
                                    autocomplete="off"
                                ></textarea>

                            </div>

                            {{-- PUBLIC --}}
                            <label class="settings-form-check">

                                <input
                                    type="checkbox"
                                    id="editSettingPublic"
                                >

                                <span>
                                    Public setting
                                </span>

                            </label>

                            <div
                                id="editSettingError"
                                class="settings-form-error"
                                hidden
                            ></div>

                        </div>

                        {{-- MODAL FOOTER --}}
                        <div class="settings-modal-footer">

                            <button
                                type="button"
                                class="settings-secondary-button"
                                data-settings-modal-close
                            >
                                Cancel
                            </button>

                            {{-- =================================================
                                PATCH save is enabled when the modal opens.
                                Delete remains live; Add is UI-only.
                            ================================================== --}}
                            <button
                                type="button"
                                class="settings-primary-button"
                                id="editSettingSaveButton"
                                disabled
                            >
                                Save Changes
                            </button>

                        </div>

                    </div>

                </div>

            @endif

            {{-- =====================================================
                DELETE SETTING MODAL â€” STEP 5C.2B
                LIVE DELETE ENDPOINT WIRING
            ====================================================== --}}
            @if($canManageSettings)

                <div
                    class="settings-modal"
                    id="settingsDeleteModal"
                    hidden
                >

                    <div
                        class="settings-modal-backdrop"
                        data-settings-delete-modal-close
                    ></div>

                    <div
                        class="settings-modal-dialog"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="settingsDeleteModalTitle"
                    >

                        {{-- MODAL HEADER --}}
                        <div class="settings-modal-header">

                            <div>

                                <h2 id="settingsDeleteModalTitle">
                                    Delete Setting
                                </h2>

                                <p>
                                    This action will permanently remove this setting.
                                </p>

                            </div>

                            <button
                                type="button"
                                class="settings-modal-close"
                                data-settings-delete-modal-close
                                aria-label="Close delete setting modal"
                            >
                                Ã—
                            </button>

                        </div>

                        {{-- MODAL BODY --}}
                        <div class="settings-modal-body">

                            <input
                                type="hidden"
                                id="deleteSettingGroup"
                            >

                            <input
                                type="hidden"
                                id="deleteSettingKey"
                            >

                            <p>
                                Are you sure you want to delete
                                <strong id="deleteSettingName"></strong>?
                            </p>

                            <div
                                id="deleteSettingError"
                                class="settings-form-error"
                                hidden
                            ></div>

                        </div>

                        {{-- MODAL FOOTER --}}
                        <div class="settings-modal-footer">

                            <button
                                type="button"
                                class="settings-secondary-button"
                                data-settings-delete-modal-close
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                class="settings-danger-button"
                                id="deleteSettingConfirmButton"
                                disabled
                            >
                                Delete Setting
                            </button>

                        </div>

                    </div>

                </div>

            @endif

        </main>

        {{-- FOOTER --}}
        <footer class="admin-footer">

            <span>
                Â© {{ date('Y') }} Eagle Global Hub LTD. All rights reserved.
            </span>

            <span>
                Version 1.0.0
            </span>

        </footer>

    </div>

</div>

<script>
    /*
    |--------------------------------------------------------------------------
    | Mobile sidebar
    |--------------------------------------------------------------------------
    */

    const menuButton = document.getElementById('adminMenuButton');
    const sidebar = document.getElementById('adminSidebar');

    menuButton?.addEventListener('click', () => {
        sidebar?.classList.toggle('is-open');
    });


    /*
    |--------------------------------------------------------------------------
    | Settings Edit Modal
    |--------------------------------------------------------------------------
    |
    | Step 5C.1B:
    | - Opens modal
    | - Populates real selected-setting values
    | - Saves through the existing PATCH endpoint
    | - Preserves the locked backend route contract
    | - Delete remains live; Add uses the verified POST endpoint
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Settings Add Modal â€” Step 5C.3C live create wiring
    |--------------------------------------------------------------------------
    */

    const addModal = document.getElementById('settingsAddModal');
    const addForm = document.getElementById('addSettingForm');
    const addGroup = document.getElementById('addSettingGroup');
    const addKey = document.getElementById('addSettingKey');
    const addType = document.getElementById('addSettingType');
    const addValue = document.getElementById('addSettingValue');
    const addPublic = document.getElementById('addSettingPublic');
    const addSaveButton = document.getElementById('addSettingSaveButton');
    const addError = document.getElementById('addSettingError');


    const editModal = document.getElementById('settingsEditModal');

    const editGroup = document.getElementById('editSettingGroup');
    const editKey = document.getElementById('editSettingKey');
    const editType = document.getElementById('editSettingType');
    const editValue = document.getElementById('editSettingValue');
    const editPublic = document.getElementById('editSettingPublic');
    const editSaveButton = document.getElementById('editSettingSaveButton');
    const editError = document.getElementById('editSettingError');


    /*
    |--------------------------------------------------------------------------
    | Settings Delete Modal â€” live delete wiring
    |--------------------------------------------------------------------------
    */

    const deleteModal =
        document.getElementById('settingsDeleteModal');

    const deleteGroup =
        document.getElementById('deleteSettingGroup');

    const deleteKey =
        document.getElementById('deleteSettingKey');

    const deleteName =
        document.getElementById('deleteSettingName');

    const deleteConfirmButton =
        document.getElementById('deleteSettingConfirmButton');

    const deleteError =
        document.getElementById('deleteSettingError');


    /*
    |--------------------------------------------------------------------------
    | Convert dataset JSON value to editable text
    |--------------------------------------------------------------------------
    */

    const formatSettingValueForInput = (value) => {

        if (value === null || value === undefined) {
            return '';
        }

        if (typeof value === 'boolean') {
            return value ? 'true' : 'false';
        }

        if (typeof value === 'object') {
            return JSON.stringify(value, null, 2);
        }

        return String(value);
    };


    /*
    |--------------------------------------------------------------------------
    | Convert editable text to request value
    |--------------------------------------------------------------------------
    */

    const prepareSettingValueForRequest = (value, type) => {

        if (type === 'json') {

            const trimmedValue = value.trim();

            if (trimmedValue === '') {
                return null;
            }

            return JSON.parse(trimmedValue);
        }

        return value;
    };


    /*
    |--------------------------------------------------------------------------
    | Open Edit Modal
    |--------------------------------------------------------------------------
    */

    const openEditModal = (button) => {

        if (
            ! editModal
            || ! editGroup
            || ! editKey
            || ! editType
            || ! editValue
            || ! editPublic
        ) {
            return;
        }

        let value = null;

        try {

            value = JSON.parse(
                button.dataset.settingValue ?? 'null'
            );

        } catch (error) {

            value = button.dataset.settingValue ?? '';

        }

        editGroup.value =
            button.dataset.settingGroup ?? '';

        editKey.value =
            button.dataset.settingKey ?? '';

        editType.value =
            button.dataset.settingType ?? '';

        editValue.value =
            formatSettingValueForInput(value);

        editPublic.checked =
            button.dataset.settingPublic === '1';

        editModal.hidden = false;

        document.body.classList.add(
            'settings-modal-open'
        );

        if (editSaveButton) {
            editSaveButton.disabled = false;
            editSaveButton.textContent = 'Save Changes';
        }

        if (editError) {
            editError.hidden = true;
            editError.textContent = '';
        }

        /*
         * Focus the editable value field after modal is visible.
         */
        window.requestAnimationFrame(() => {
            editValue.focus();
            editValue.select();
        });
    };


    /*
    |--------------------------------------------------------------------------
    | Close Edit Modal
    |--------------------------------------------------------------------------
    */

    const closeEditModal = () => {

        if (! editModal) {
            return;
        }

        editModal.hidden = true;

        document.body.classList.remove(
            'settings-modal-open'
        );

        if (editSaveButton) {
            editSaveButton.disabled = true;
            editSaveButton.textContent = 'Save Changes';
        }

        if (editError) {
            editError.hidden = true;
            editError.textContent = '';
        }
    };


    /*
    |--------------------------------------------------------------------------
    | Open Add Modal â€” Step 5C.3C
    |--------------------------------------------------------------------------
    */

    const openAddModal = (button) => {

        if (
            ! addModal
            || ! addForm
            || ! addGroup
            || ! addKey
            || ! addType
            || ! addValue
            || ! addPublic
            || ! addSaveButton
        ) {
            return;
        }

        addForm.reset();

        addGroup.value =
            button.dataset.settingGroup ?? '';

        addType.value = 'string';
        addPublic.checked = false;

        addSaveButton.disabled = true;
        addSaveButton.textContent = 'Add Setting';

        if (addError) {
            addError.hidden = true;
            addError.textContent = '';
        }

        addModal.hidden = false;

        document.body.classList.add(
            'settings-modal-open'
        );

        window.requestAnimationFrame(() => {
            addKey.focus();
        });
    };


    /*
    |--------------------------------------------------------------------------
    | Close Add Modal
    |--------------------------------------------------------------------------
    */

    const closeAddModal = () => {

        if (! addModal) {
            return;
        }

        addModal.hidden = true;

        if (addForm) {
            addForm.reset();
        }

        if (addGroup) {
            addGroup.value = '';
        }

        if (addSaveButton) {
            addSaveButton.disabled = true;
            addSaveButton.textContent = 'Add Setting';
        }

        if (addError) {
            addError.hidden = true;
            addError.textContent = '';
        }

        document.body.classList.remove(
            'settings-modal-open'
        );
    };


    /*
    |--------------------------------------------------------------------------
    | Validate Add Setting form
    |--------------------------------------------------------------------------
    */

    const updateAddSaveState = () => {

        if (
            ! addGroup
            || ! addKey
            || ! addSaveButton
        ) {
            return;
        }

        const hasGroup = addGroup.value.trim() !== '';
        const hasKey = addKey.value.trim() !== '';

        addSaveButton.disabled = ! (hasGroup && hasKey);
    };


    addKey?.addEventListener('input', updateAddSaveState);


    /*
    |--------------------------------------------------------------------------
    | Open Delete Modal
    |--------------------------------------------------------------------------
    */

    const openDeleteModal = (button) => {

        if (
            ! deleteModal
            || ! deleteGroup
            || ! deleteKey
            || ! deleteName
            || ! deleteConfirmButton
        ) {
            return;
        }

        deleteGroup.value =
            button.dataset.settingGroup ?? '';

        deleteKey.value =
            button.dataset.settingKey ?? '';

        deleteName.textContent =
            `${deleteGroup.value}.${deleteKey.value}`;

        deleteConfirmButton.disabled = false;
        deleteConfirmButton.textContent = 'Delete Setting';

        if (deleteError) {
            deleteError.hidden = true;
            deleteError.textContent = '';
        }

        deleteModal.hidden = false;

        document.body.classList.add(
            'settings-modal-open'
        );
    };


    /*
    |--------------------------------------------------------------------------
    | Close Delete Modal
    |--------------------------------------------------------------------------
    */

    const closeDeleteModal = () => {

        if (! deleteModal) {
            return;
        }

        deleteModal.hidden = true;

        if (deleteConfirmButton) {
            deleteConfirmButton.disabled = true;
            deleteConfirmButton.textContent = 'Delete Setting';
        }

        if (deleteError) {
            deleteError.hidden = true;
            deleteError.textContent = '';
        }

        if (deleteGroup) {
            deleteGroup.value = '';
        }

        if (deleteKey) {
            deleteKey.value = '';
        }

        if (deleteName) {
            deleteName.textContent = '';
        }

        document.body.classList.remove(
            'settings-modal-open'
        );
    };


    /*
    |--------------------------------------------------------------------------
    | Add button â€” Step 5C.3C
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('[data-setting-add]')
        .forEach((button) => {

            button.addEventListener('click', () => {
                openAddModal(button);
            });

        });


    /*
    |--------------------------------------------------------------------------
    | Edit buttons
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('[data-setting-edit]')
        .forEach((button) => {

            button.addEventListener('click', () => {
                openEditModal(button);
            });

        });


    /*
    |--------------------------------------------------------------------------
    | Delete buttons
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('[data-setting-delete]')
        .forEach((button) => {

            button.addEventListener('click', () => {
                openDeleteModal(button);
            });

        });


    /*
    |--------------------------------------------------------------------------
    | Modal close controls
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('[data-settings-add-modal-close]')
        .forEach((button) => {

            button.addEventListener('click', () => {
                closeAddModal();
            });

        });

    document
        .querySelectorAll('[data-settings-modal-close]')
        .forEach((button) => {

            button.addEventListener('click', () => {
                closeEditModal();
            });

        });

    document
        .querySelectorAll('[data-settings-delete-modal-close]')
        .forEach((button) => {

            button.addEventListener('click', () => {
                closeDeleteModal();
            });

        });


    /*
    |--------------------------------------------------------------------------
    | Escape key closes modal
    |--------------------------------------------------------------------------
    */

    document.addEventListener('keydown', (event) => {

        if (event.key !== 'Escape') {
            return;
        }

        if (
            deleteModal
            && ! deleteModal.hidden
        ) {
            closeDeleteModal();
            return;
        }

        if (
            addModal
            && ! addModal.hidden
        ) {
            closeAddModal();
            return;
        }

        if (
            editModal
            && ! editModal.hidden
        ) {
            closeEditModal();
        }

    });


    /*
    |--------------------------------------------------------------------------
    | Add Setting â€” Step 5C.3C live POST create
    |--------------------------------------------------------------------------
    */

    addForm?.addEventListener('submit', async (event) => {

        event.preventDefault();

        if (
            ! addGroup
            || ! addKey
            || ! addType
            || ! addValue
            || ! addPublic
            || ! addSaveButton
        ) {
            return;
        }

        const group = addGroup.value.trim();
        const key = addKey.value.trim();
        const type = addType.value;

        if (group === '' || key === '') {
            updateAddSaveState();
            return;
        }

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        addSaveButton.disabled = true;
        addSaveButton.textContent = 'Adding...';

        if (addError) {
            addError.hidden = true;
            addError.textContent = '';
        }

        try {

            const requestValue =
                prepareSettingValueForRequest(
                    addValue.value,
                    type
                );

            const response = await fetch(
                `/admin/settings/${encodeURIComponent(group)}/${encodeURIComponent(key)}`,
                {
                    method: 'POST',

                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ?? '',
                    },

                    body: JSON.stringify({
                        value: requestValue,
                        type: type,
                        is_public: addPublic.checked,
                    }),
                }
            );

            let data = {};

            const contentType =
                response.headers.get('content-type') ?? '';

            if (contentType.includes('application/json')) {
                data = await response.json();
            }

            if (! response.ok) {

                let message =
                    data.message
                    ?? 'Unable to create the setting.';

                if (data.errors) {

                    const firstError = Object
                        .values(data.errors)
                        .flat()
                        .at(0);

                    if (firstError) {
                        message = firstError;
                    }
                }

                throw new Error(message);
            }

            closeAddModal();

            window.location.reload();

        } catch (error) {

            if (addError) {

                addError.textContent =
                    error instanceof Error
                        ? error.message
                        : 'Unable to create the setting.';

                addError.hidden = false;
            }

            updateAddSaveState();

        } finally {

            addSaveButton.textContent = 'Add Setting';
        }

    });


    /*
    |--------------------------------------------------------------------------
    | Delete Setting
    |--------------------------------------------------------------------------
    */

    deleteConfirmButton?.addEventListener('click', async () => {

        if (
            ! deleteGroup
            || ! deleteKey
        ) {
            return;
        }

        const group = deleteGroup.value;
        const key = deleteKey.value;

        if (group === '' || key === '') {
            return;
        }

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        deleteConfirmButton.disabled = true;
        deleteConfirmButton.textContent = 'Deleting...';

        if (deleteError) {
            deleteError.hidden = true;
            deleteError.textContent = '';
        }

        try {

            const response = await fetch(
                `/admin/settings/${encodeURIComponent(group)}/${encodeURIComponent(key)}`,
                {
                    method: 'DELETE',

                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ?? '',
                    },
                }
            );

            let data = {};

            const contentType =
                response.headers.get('content-type') ?? '';

            if (contentType.includes('application/json')) {

                data = await response.json();

            }

            if (! response.ok) {

                throw new Error(
                    data.message
                    ?? 'Unable to delete the setting.'
                );
            }

            closeDeleteModal();

            window.location.reload();

        } catch (error) {

            if (deleteError) {

                deleteError.textContent =
                    error instanceof Error
                        ? error.message
                        : 'Unable to delete the setting.';

                deleteError.hidden = false;
            }

            deleteConfirmButton.disabled = false;

        } finally {

            deleteConfirmButton.textContent =
                'Delete Setting';
        }

    });


    /*
    |--------------------------------------------------------------------------
    | Save Edit Setting
    |--------------------------------------------------------------------------
    */

    editSaveButton?.addEventListener('click', async () => {

        if (
            ! editGroup
            || ! editKey
            || ! editType
            || ! editValue
            || ! editPublic
        ) {
            return;
        }

        const group = editGroup.value;
        const key = editKey.value;
        const type = editType.value;

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        editSaveButton.disabled = true;
        editSaveButton.textContent = 'Saving...';

        if (editError) {
            editError.hidden = true;
            editError.textContent = '';
        }

        try {

            const requestValue =
                prepareSettingValueForRequest(
                    editValue.value,
                    type
                );

            const response = await fetch(
                `/admin/settings/${encodeURIComponent(group)}/${encodeURIComponent(key)}`,
                {
                    method: 'PATCH',

                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken ?? '',
                    },

                    body: JSON.stringify({
                        value: requestValue,
                        type: type,
                        is_public: editPublic.checked,
                    }),
                }
            );

            const data = await response.json();

            if (! response.ok) {

                let message =
                    data.message
                    ?? 'Unable to save the setting.';

                if (data.errors) {

                    const firstError = Object
                        .values(data.errors)
                        .flat()
                        .at(0);

                    if (firstError) {
                        message = firstError;
                    }
                }

                throw new Error(message);
            }

            closeEditModal();

            window.location.reload();

        } catch (error) {

            if (editError) {

                editError.textContent =
                    error instanceof Error
                        ? error.message
                        : 'Unable to save the setting.';

                editError.hidden = false;
            }

            editSaveButton.disabled = false;

        } finally {

            editSaveButton.textContent =
                'Save Changes';
        }

    });
</script>

</body>
</html>
