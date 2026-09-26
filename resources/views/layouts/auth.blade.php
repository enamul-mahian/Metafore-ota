@php
    $logoUrl = $site->imageUrl('logo');
    $pageTitle = trim($__env->yieldContent('title', 'Eagle Global Hub LTD'));
    $pageDescription = trim($__env->yieldContent('meta_description', 'Secure account access for Eagle Global Hub LTD flight, hotel, tour, visa and work visa services.'));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1f4d">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ trim($__env->yieldContent('robots', 'noindex,follow')) }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Eagle Global Hub">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $site->imageUrl('home.hero') }}">
    <link rel="icon" href="{{ $logoUrl }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/site.css', 'resources/js/app.js', 'resources/js/site.js'])

    @include('partials.site.tracking-head')
</head>

<body class="egh-site auth-page">
    @include('partials.site.tracking-body')
    @include('partials.site.icons')

    <main class="auth2" id="main-content">

        <section class="auth2__visual" style="background-image:url('{{ $site->imageUrl('home.hero') }}')">
            <div class="auth2__visual-top">
                <a href="{{ route('home') }}" class="logo logo--light" aria-label="Eagle Global Hub home">
                    <img src="{{ $logoUrl }}" alt="Eagle Global Hub logo" class="logo__mark" width="76" height="40">
                    <span class="logo__txt"><b>EAGLE</b><small>GLOBAL HUB LTD.</small></span>
                </a>
                <a href="{{ route('home') }}" class="auth2__back">
                    <svg class="ic"><use href="#i-chev-l"/></svg>Back to website
                </a>
            </div>

            <div class="auth2__copy">
                <span class="auth2__eyebrow">Secure travel account</span>
                <h1>@yield('hero-title', 'Your journey starts with secure account access.')</h1>
                <p>@yield('hero-description', 'Search, review and manage your travel from one verified account.')</p>

                <ul class="auth2__ticks">
                    <li><svg class="ic"><use href="#i-shield"/></svg><span><b>Verified account access</b>Protected areas require a verified email address.</span></li>
                    <li><svg class="ic"><use href="#i-plane"/></svg><span><b>Clear booking flow</b>See each booking and payment status as you continue.</span></li>
                    <li><svg class="ic"><use href="#i-lock"/></svg><span><b>Server-checked actions</b>Booking and payment decisions stay on our secure servers.</span></li>
                </ul>
            </div>

            <small class="auth2__foot">&copy; {{ now()->year }} Eagle Global Hub LTD</small>
        </section>

        <section class="auth2__panel">
            <div class="auth2__mobile-brand">
                <a href="{{ route('home') }}" class="logo" aria-label="Eagle Global Hub home">
                    <img src="{{ $logoUrl }}" alt="Eagle Global Hub logo" class="logo__mark" width="76" height="40">
                    <span class="logo__txt"><b>EAGLE</b><small>GLOBAL HUB LTD.</small></span>
                </a>
                <a href="{{ route('home') }}" class="auth2__back auth2__back--dark">
                    <svg class="ic"><use href="#i-chev-l"/></svg>Website
                </a>
            </div>

            <div class="auth-card auth2__card">

                @if ($errors->any())
                    <div class="auth-alert auth-alert-error" role="alert">
                        <strong>Please check the form.</strong>

                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (
                    session('status')
                    && session('status') !== 'verification-link-sent'
                )
                    <div class="auth-alert auth-alert-success" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')

                <div class="auth-card-meta">
                    <span><svg class="ic"><use href="#i-lock"/></svg>Secure account access</span>

                    <a href="{{ route('home') }}">Eagle Global Hub LTD</a>
                </div>

            </div>
        </section>

    </main>
</body>
</html>
