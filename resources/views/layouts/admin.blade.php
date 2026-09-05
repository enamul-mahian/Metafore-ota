<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') | Eagle Global Hub LTD</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
.egh-admin{min-height:100vh;background:#f5f7fb;color:#172944;font-family:Arial,sans-serif}.egh-shell{display:flex;min-height:100vh}.egh-side{width:250px;background:#fff;border-right:1px solid #e5e9f2;padding:24px 16px}.egh-brand{font-weight:800;font-size:18px;margin-bottom:28px}.egh-nav{display:grid;gap:8px}.egh-nav a{padding:11px 12px;border-radius:8px;text-decoration:none;color:#475569}.egh-nav a:hover,.egh-nav a.active{background:#eef3ff;color:#244fc7}.egh-main{flex:1;min-width:0}.egh-top{height:68px;background:#fff;border-bottom:1px solid #e5e9f2;display:flex;align-items:center;justify-content:flex-end;padding:0 28px}.egh-user{font-size:14px}.egh-content{padding:28px}.egh-card{background:#fff;border:1px solid #e5e9f2;border-radius:12px;padding:20px}.egh-button{display:inline-block;padding:10px 14px;border:0;border-radius:8px;background:#315ff4;color:#fff;text-decoration:none;cursor:pointer}.egh-button.secondary{background:#e9eef7;color:#273852}.egh-alert{margin-bottom:18px;padding:12px 14px;border-radius:8px;background:#e9f8ef;color:#22643b}@media(max-width:800px){.egh-side{width:190px}.egh-content{padding:16px}}
    </style>
</head>
<body class="egh-admin">
<div class="egh-shell">
    <aside class="egh-side">
        <div class="egh-brand">Eagle Global Hub LTD</div>
        <nav class="egh-nav">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('admin.bookings.index') }}" class="{{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">Bookings</a>
            @can('reports.view')
                <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">Reports</a>
            @endcan
            @can('agents.view')
                <a href="{{ route('admin.agents.index') }}" class="{{ request()->routeIs('admin.agents.*') ? 'active' : '' }}">Agents</a>
            @endcan
            @can('affiliates.view')
                <a href="{{ route('admin.affiliates.index') }}" class="{{ request()->routeIs('admin.affiliates.*') ? 'active' : '' }}">Affiliates</a>
            @endcan
            @can('students.view')
                <a href="{{ route('admin.students.index') }}" class="{{ request()->routeIs('admin.students.*') ? 'active' : '' }}">Students</a>
            @endcan
            @can('institutions.view')
                <a href="{{ route('admin.institutions.index') }}" class="{{ request()->routeIs('admin.institutions.*') ? 'active' : '' }}">Institutions</a>
            @endcan
            @can('users.view')
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a>
            @endcan
            @can('roles.view')
                <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Roles &amp; Permissions</a>
            @endcan
            @can('master-data.view')
                <a href="{{ route('admin.master-data.manage') }}" class="{{ request()->routeIs('admin.master-data.*') ? 'active' : '' }}">Master Data</a>
                <a href="{{ route('admin.categories.manage') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">Categories</a>
                <a href="{{ route('admin.currencies.manage') }}" class="{{ request()->routeIs('admin.currencies.*') ? 'active' : '' }}">Currencies</a>
                <a href="{{ route('admin.languages.manage') }}" class="{{ request()->routeIs('admin.languages.*') ? 'active' : '' }}">Languages</a>
            @endcan
            @role('super-admin')
                <a href="{{ route('admin.features.index') }}">Feature Control</a>
            @endrole
            @can('settings.view')
                <a href="{{ route('admin.settings.manage') }}">Settings</a>
            @endcan
            @can('system-logs.view')
                <a href="{{ route('admin.system-logs.index') }}" class="{{ request()->routeIs('admin.system-logs.*') ? 'active' : '' }}">System Logs</a>
            @endcan
        </nav>
    </aside>
    <div class="egh-main">
        <header class="egh-top">
            <div class="egh-user">{{ auth()->user()->name }} · {{ auth()->user()->getRoleNames()->join(', ') }}</div>
        </header>
        <main class="egh-content">
            @if(session('status'))
                <div class="egh-alert">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
