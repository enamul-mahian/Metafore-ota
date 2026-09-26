@extends('layouts.site')

@section('title', 'Account Overview')
@section('seo_title', 'My Account | Eagle Global Hub')
@section('robots', 'noindex,nofollow')
@section('body_class', 'account-body')

@section('content')
    @php($user = auth()->user())

    <section class="page account-page">
        <div class="container">
            <nav class="crumbs" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>My Account</span>
            </nav>

            <div class="account-layout">
                @include('account.partials.side-menu', ['active' => 'overview'])

                <div class="stack">
                    <div class="card account-hello">
                        <div class="account-hello__id">
                            <span class="account-hello__av" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            <div>
                                <p class="account-hello__kicker">Account Overview</p>
                                <h1 class="h1">{{ $user->name }}</h1>
                                <p class="muted">{{ $user->email }}</p>
                            </div>
                        </div>
                        <span class="badge badge--success"><svg class="ic"><use href="#i-check"/></svg>Verified</span>
                    </div>

                    <div class="grid-3 account-facts">
                        <div class="card account-fact">
                            <span class="account-fact__ic c-green"><svg class="ic"><use href="#i-shield"/></svg></span>
                            <div>
                                <small>Email verification</small>
                                <b>Verified</b>
                                <small>{{ $user->email_verified_at?->format('M j, Y') ?? 'Verified' }}</small>
                            </div>
                        </div>
                        <div class="card account-fact">
                            <span class="account-fact__ic c-blue"><svg class="ic"><use href="#i-cal"/></svg></span>
                            <div>
                                <small>Member since</small>
                                <b>{{ $user->created_at?->format('M j, Y') ?? '—' }}</b>
                            </div>
                        </div>
                        <div class="card account-fact">
                            <span class="account-fact__ic c-orange"><svg class="ic"><use href="#i-lock"/></svg></span>
                            <div>
                                <small>Security</small>
                                <b>Password protected</b>
                                <a class="link small" href="{{ route('account.profile') }}#security">Change password</a>
                            </div>
                        </div>
                    </div>

                    <section class="card" aria-labelledby="account-shortcuts-title">
                        <h2 class="card__title" id="account-shortcuts-title">Continue planning</h2>

                        <div class="account-shortcuts">
                            <a href="{{ route('account.profile') }}" class="account-shortcut">
                                <span class="account-shortcut__ic"><svg class="ic"><use href="#i-user"/></svg></span>
                                <span><b>Profile &amp; security</b><small>Update your name, email and password</small></span>
                                <svg class="ic account-shortcut__go"><use href="#i-chev-r"/></svg>
                            </a>

                            @feature('flights')
                                @can('flights.search')
                                    <a href="{{ route('flights.index') }}" class="account-shortcut">
                                        <span class="account-shortcut__ic"><svg class="ic"><use href="#i-plane"/></svg></span>
                                        <span><b>Search Flights</b><small>Compare fares and start a new booking</small></span>
                                        <svg class="ic account-shortcut__go"><use href="#i-chev-r"/></svg>
                                    </a>
                                @endcan
                            @endfeature

                            @feature('bookings')
                                @can('flights.book')
                                    <a href="{{ route('bookings.index') }}" class="account-shortcut">
                                        <span class="account-shortcut__ic"><svg class="ic"><use href="#i-briefcase"/></svg></span>
                                        <span><b>My Bookings</b><small>Order, payment and invoice records</small></span>
                                        <svg class="ic account-shortcut__go"><use href="#i-chev-r"/></svg>
                                    </a>
                                @endcan
                            @endfeature

                            @if (Route::has('work-visa.applications.index'))
                                <a href="{{ route('work-visa.applications.index') }}" class="account-shortcut">
                                    <span class="account-shortcut__ic"><svg class="ic"><use href="#i-file"/></svg></span>
                                    <span><b>Work Visa Applications</b><small>Track your application status</small></span>
                                    <svg class="ic account-shortcut__go"><use href="#i-chev-r"/></svg>
                                </a>
                            @endif

                            @feature('dashboard')
                                <a href="{{ route('dashboard') }}" class="account-shortcut">
                                    <span class="account-shortcut__ic"><svg class="ic"><use href="#i-grid"/></svg></span>
                                    <span><b>Dashboard</b><small>All travel services in one place</small></span>
                                    <svg class="ic account-shortcut__go"><use href="#i-chev-r"/></svg>
                                </a>
                            @endfeature
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>
@endsection
