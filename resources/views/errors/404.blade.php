@extends('layouts.site')

@section('title', 'Page Not Found')
@section('body_class', 'public-page-body')

@section('content')

    <main class="public-page-container">

        <section class="public-page-hero public-page-error">
            <span class="public-page-kicker">
                404
            </span>

            <h1>
                Page not found
            </h1>

            <p>
                The page you requested is not available. You can return home
                or continue through the currently available account services.
            </p>

            <div class="public-page-actions">
                <a
                    href="{{ route('home') }}"
                    class="site-button site-button-primary"
                >
                    Home
                </a>

                @auth
                    @feature('dashboard')
                        <a
                            href="{{ route('dashboard') }}"
                            class="site-button site-button-secondary"
                        >
                            Dashboard
                        </a>
                    @endfeature
                @else
                    <a
                        href="{{ route('login') }}"
                        class="site-button site-button-secondary"
                    >
                        Login
                    </a>
                @endauth
            </div>
        </section>

    </main>

@endsection
