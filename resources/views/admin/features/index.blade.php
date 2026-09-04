<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Feature Control | Eagle Global Hub LTD</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-brand">
            <div class="admin-brand-mark" aria-hidden="true">&#9992;</div>
            <div class="admin-brand-copy">
                <strong>Eagle Global Hub LTD</strong>
                <span>Super Admin Control</span>
            </div>
        </div>

        <nav class="admin-nav" aria-label="Administration">
            <div class="admin-nav-section">
                <span class="admin-nav-title">MAIN</span>
                <a href="{{ route('home') }}" class="admin-nav-link">
                    <span aria-hidden="true">&#8962;</span>
                    <span>Website</span>
                </a>
                @feature('dashboard')
                    <a href="{{ route('dashboard') }}" class="admin-nav-link">
                        <span aria-hidden="true">&#9638;</span>
                        <span>Dashboard</span>
                    </a>
                @endfeature
            </div>

            <div class="admin-nav-section">
                <span class="admin-nav-title">SYSTEM</span>
                <a
                    href="{{ route('admin.features.index') }}"
                    class="admin-nav-link active"
                >
                    <span aria-hidden="true">&#9881;</span>
                    <span>Feature Control</span>
                </a>
                <a
                    href="{{ route('admin.settings.manage') }}"
                    class="admin-nav-link"
                >
                    <span aria-hidden="true">&#9776;</span>
                    <span>Settings</span>
                </a>
            </div>
        </nav>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button
                    type="button"
                    class="admin-menu-button"
                    id="adminMenuButton"
                    aria-label="Toggle navigation"
                >
                    <span></span><span></span><span></span>
                </button>
            </div>

            <div class="admin-user">
                <div class="admin-user-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="admin-user-copy">
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>Super Admin</span>
                </div>
            </div>
        </header>

        <main class="settings-page feature-control-page">
            <div class="settings-page-heading">
                <div class="settings-title-wrap">
                    <div class="settings-title-icon" aria-hidden="true">&#9881;</div>
                    <div>
                        <h1>Feature Control</h1>
                        <span class="settings-access-mode">Super Admin only</span>
                    </div>
                </div>

                <div class="settings-breadcrumb">
                    @feature('dashboard')
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    @else
                        <a href="{{ route('home') }}">Website</a>
                    @endfeature
                    <span>&rsaquo;</span>
                    <strong>Feature Control</strong>
                </div>
            </div>

            @if (session('status'))
                <div class="feature-control-alert" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="feature-control-alert is-error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="feature-control-notice" aria-label="Safety boundary">
                <strong>Visibility is separate from provider activation.</strong>
                <p>
                    Enabling a feature exposes only its permitted website UI.
                    It does not configure suppliers, credentials, payments, or
                    live flight order execution.
                </p>
            </section>

            <section class="feature-control-list" aria-label="Registered features">
                <div class="feature-control-header" aria-hidden="true">
                    <span>Feature</span>
                    <span>Status</span>
                    <span>Public</span>
                    <span>Authenticated</span>
                    <span>Admin</span>
                    <span>Unavailable message</span>
                    <span>Actions</span>
                </div>

                @foreach ($features as $feature)
                    <form
                        method="POST"
                        action="{{ route('admin.features.update', $feature['key']) }}"
                        class="feature-control-row"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="feature-control-name">
                            <strong>{{ $feature['label'] }}</strong>
                            <code>{{ $feature['key'] }}</code>
                            <small>{{ $feature['description'] }}</small>
                        </div>

                        <label class="feature-control-toggle">
                            <input type="hidden" name="enabled" value="0">
                            <input
                                type="checkbox"
                                name="enabled"
                                value="1"
                                @checked($feature['enabled'])
                            >
                            <span @class(['is-enabled' => $feature['enabled']])>
                                {{ $feature['enabled'] ? 'Enabled' : 'Disabled' }}
                            </span>
                        </label>

                        @foreach ([
                            'public_visible' => 'Public visibility',
                            'authenticated_visible' => 'Authenticated visibility',
                            'admin_visible' => 'Admin visibility',
                        ] as $field => $label)
                            <label class="feature-control-check">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input
                                    type="checkbox"
                                    name="{{ $field }}"
                                    value="1"
                                    aria-label="{{ $feature['label'] }} {{ $label }}"
                                    @checked($feature[$field])
                                >
                                <span>{{ $feature[$field] ? 'Visible' : 'Hidden' }}</span>
                            </label>
                        @endforeach

                        <label class="feature-control-message">
                            <span class="sr-only">
                                {{ $feature['label'] }} unavailable message
                            </span>
                            <input
                                type="text"
                                name="message"
                                value="{{ $feature['message'] }}"
                                maxlength="500"
                                placeholder="This feature is currently unavailable."
                            >
                        </label>

                        <button type="submit" class="site-button site-button-primary">
                            Save
                        </button>
                    </form>
                @endforeach
            </section>
        </main>
    </div>
</div>
</body>
</html>
