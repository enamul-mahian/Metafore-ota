<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'MetaFore OTA')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main class="auth-shell">

        <section class="auth-visual">
            <div class="auth-visual-overlay"></div>

            <div class="auth-brand">
                <div class="auth-brand-icon">
                    ✈
                </div>

                <span>MetaFore OTA</span>
            </div>

            <div class="auth-visual-content">
                <span class="auth-eyebrow">
                    YOUR JOURNEY STARTS HERE
                </span>

                <h1>
                    @yield(
                        'hero-title',
                        'Explore The World With MetaFore OTA'
                    )
                </h1>

                <p>
                    @yield(
                        'hero-description',
                        'Your trusted travel companion for unforgettable journeys.'
                    )
                </p>
            </div>
        </section>

        <section class="auth-content">

            <div class="auth-mobile-brand">
                <span class="auth-brand-icon">
                    ✈
                </span>

                <strong>
                    MetaFore OTA
                </strong>
            </div>

            <div class="auth-card">

                @if ($errors->any())
                    <div class="auth-alert auth-alert-error">
                        <strong>
                            Please check the form.
                        </strong>

                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (
                    session('status')
                    && session('status') !== 'verification-link-sent'
                )
                    <div class="auth-alert auth-alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')

            </div>

        </section>

    </main>
</body>
</html>