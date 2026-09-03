@extends('layouts.site')

@section('title', 'Account Overview')
@section('body_class', 'account-body')

@section('content')

    <main class="account-container">

        <section class="account-hero">

            <div>
                <span class="account-kicker">
                    YOUR ACCOUNT
                </span>

                <h1>
                    Account Overview
                </h1>

                <p>
                    Review the account information currently stored for your
                    Eagle Global Hub LTD travel account.
                </p>
            </div>

            <div class="account-identity">
                <span class="account-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>

                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->email }}</small>
                </div>
            </div>

        </section>

        <section class="account-grid">

            <article class="account-panel">

                <div class="account-panel-heading">
                    <div>
                        <span class="account-kicker">
                            PROFILE
                        </span>

                        <h2>
                            Personal details
                        </h2>
                    </div>
                </div>

                <dl class="account-detail-list">

                    <div>
                        <dt>Full Name</dt>
                        <dd>{{ auth()->user()->name }}</dd>
                    </div>

                    <div>
                        <dt>Email Address</dt>
                        <dd>{{ auth()->user()->email }}</dd>
                    </div>

                </dl>

            </article>

            <article class="account-panel">

                <div class="account-panel-heading">
                    <div>
                        <span class="account-kicker">
                            SECURITY
                        </span>

                        <h2>
                            Account status
                        </h2>
                    </div>
                </div>

                <dl class="account-detail-list">

                    <div>
                        <dt>Email Verification</dt>

                        <dd class="account-status-verified">
                            Verified
                        </dd>
                    </div>

                    <div>
                        <dt>Verified On</dt>

                        <dd>
                            {{
                                auth()->user()->email_verified_at
                                    ?->format('M j, Y')
                                ?? 'Verified'
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt>Account Created</dt>

                        <dd>
                            {{
                                auth()->user()->created_at
                                    ?->format('M j, Y')
                                ?? '—'
                            }}
                        </dd>
                    </div>

                </dl>

            </article>

        </section>

        <section class="account-actions">

            <div>
                <span class="account-kicker">
                    TRAVEL
                </span>

                <h2>
                    Continue planning
                </h2>

                <p>
                    Return to your dashboard or continue to flight search.
                </p>
            </div>

            <div class="account-action-buttons">

                <a
                    href="{{ route('dashboard') }}"
                    class="site-button site-button-secondary"
                >
                    Dashboard
                </a>

                @can('flights.search')
                    <a
                        href="{{ route('flights.index') }}"
                        class="site-button site-button-primary"
                    >
                        Search Flights
                    </a>
                @endcan

            </div>

        </section>

    </main>

@endsection