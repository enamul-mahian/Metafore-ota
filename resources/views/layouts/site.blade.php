@php
    $routeName = request()->route()?->getName();
    $pageTitle = trim($__env->yieldContent('seo_title'))
        ?: ($siteSeo['title'] ?: trim($__env->yieldContent('title', 'Travel')).' | Eagle Global Hub LTD');
    $pageDescription = trim($__env->yieldContent('seo_description'))
        ?: ($siteSeo['description'] ?: trim($__env->yieldContent('meta_description', 'Flights, hotels, tours, visa and work visa services from Eagle Global Hub LTD.')));
    $pageRobots = trim($__env->yieldContent('robots', 'index,follow'));
    $pageImage = trim($__env->yieldContent('og_image')) ?: $site->imageUrl('home.hero');
    $logoUrl = $site->imageUrl('logo');
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
    <meta name="robots" content="{{ $pageRobots }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Eagle Global Hub">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">
    <link rel="icon" href="{{ $logoUrl }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Caveat:wght@600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/site.css', 'resources/js/app.js', 'resources/js/site.js'])

    @include('partials.site.tracking-head')

    @stack('head')
</head>

<body class="site-body egh-site @yield('body_class')">
    @include('partials.site.tracking-body')
    @include('partials.site.icons')

    <a href="#main-content" class="site-skip-link">Skip to content</a>

    {{-- Top bar --}}
    <div class="topbar">
        <div class="container topbar__in">
            <div class="topbar__left">
                @isset($siteContact['phone'])
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $siteContact['phone']) }}"><svg class="ic"><use href="#i-phone"/></svg>{{ $siteContact['phone'] }}</a>
                @endisset
                @isset($siteContact['email'])
                    <a href="mailto:{{ $siteContact['email'] }}" class="hide-sm"><svg class="ic"><use href="#i-mail"/></svg>{{ $siteContact['email'] }}</a>
                @endisset
                @if (! isset($siteContact['phone']) && ! isset($siteContact['email']))
                    <span><svg class="ic"><use href="#i-globe"/></svg>Flights · Hotels · Tours · Visa · Work Visa</span>
                @endif
            </div>
            <div class="topbar__right">
                <nav class="topbar__links" aria-label="Utility">
                    @feature('support')
                        <a href="{{ route('support') }}">Help</a>
                    @endfeature
                    @feature('about')
                        <a href="{{ route('about') }}">About</a>
                    @endfeature
                    @if (Route::has('contact'))
                        <a href="{{ route('contact') }}">Contact</a>
                    @endif
                    @auth
                        @feature('bookings')
                            @can('flights.book')
                                <a href="{{ route('bookings.index') }}">Manage Booking</a>
                            @endcan
                        @endfeature
                        @role('admin|super-admin')
                            <a href="{{ route('dashboard') }}">Admin</a>
                        @endrole
                        @role('super-admin')
                            <a href="{{ route('admin.features.index') }}">Feature Control</a>
                        @endrole
                    @endauth
                </nav>
                @auth
                    @feature('account')
                        <a href="{{ route('account.overview') }}" class="account">
                            <span class="account__av">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="hide-sm"><b>{{ auth()->user()->name }}</b><small>My Account</small></span>
                        </a>
                    @endfeature
                    <form method="POST" action="{{ route('logout') }}" class="topbar__logout">
                        @csrf
                        <button type="submit">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="account">
                        <span class="account__av"><svg class="ic"><use href="#i-user"/></svg></span>
                        <span><b>Login / Sign Up</b><small class="hide-sm">My Account</small></span>
                    </a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Header --}}
    <header class="header" id="header">
        <div class="container header__in">
            <a href="{{ route('home') }}" class="logo" aria-label="Eagle Global Hub home">
                <img src="{{ $logoUrl }}" alt="Eagle Global Hub logo" class="logo__mark" width="76" height="40">
                <span class="logo__txt"><b>EAGLE</b><small>GLOBAL HUB LTD.</small></span>
            </a>
            <nav class="nav" id="nav" aria-label="Primary navigation">
                @foreach ($siteNavigation as $item)
                    <a href="{{ $item['href'] }}" @class(['nav__link', 'nav__link--hot' => $item['badge'], 'is-active' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
                        <svg class="ic"><use href="#i-{{ $item['icon'] }}"/></svg>{{ $item['label'] }}@if ($item['badge'])<em>{{ $item['badge'] }}</em>@endif
                    </a>
                @endforeach
            </nav>
            <button class="burger" id="burger" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="drawer"><svg class="ic"><use href="#i-menu"/></svg></button>
        </div>
    </header>

    {{-- Mobile drawer --}}
    <div class="drawer" id="drawer" aria-hidden="true">
        <div class="drawer__panel" role="dialog" aria-modal="true" aria-label="Menu">
            <div class="drawer__head">
                <a href="{{ route('home') }}" class="logo"><img src="{{ $logoUrl }}" alt="" class="logo__mark"><span class="logo__txt"><b>EAGLE</b><small>GLOBAL HUB LTD.</small></span></a>
                <button class="icon-btn" id="drawerClose" type="button" aria-label="Close menu"><svg class="ic"><use href="#i-x"/></svg></button>
            </div>
            <div class="drawer__nav" id="drawerNav"></div>
            <div class="drawer__foot">
                @auth
                    @feature('account')<a href="{{ route('account.overview') }}">My Account</a>@endfeature
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}">Create Account</a>
                @endauth
                @feature('support')<a href="{{ route('support') }}">Help</a>@endfeature
                @feature('about')<a href="{{ route('about') }}">About</a>@endfeature
                @if (Route::has('contact'))<a href="{{ route('contact') }}">Contact</a>@endif
            </div>
        </div>
    </div>

    @if (app()->environment(['local', 'testing']) && ! request()->routeIs('home'))
        <div class="site-environment-banner" role="status">
            <strong>Development / sandbox environment:</strong>
            flight availability may use fixture or supplier sandbox data and
            must not be treated as live bookable airline inventory.
        </div>
    @endif

    @if (request()->attributes->get('super_admin_feature_preview') === true)
        <div class="site-environment-banner" role="status">
            <strong>Super Admin Preview:</strong>
            this feature is currently disabled or hidden for other users.
        </div>
    @endif

    <main id="main-content">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="footer">
        <div class="container footer__grid">
            <div class="footer__brand">
                <a href="{{ route('home') }}" class="logo logo--light"><img src="{{ $logoUrl }}" alt="Eagle Global Hub logo" class="logo__mark"><span class="logo__txt"><b>EAGLE</b><small>GLOBAL HUB LTD.</small></span></a>
                <p>Flights, hotels, tours, visa and work visa services. We make travel, work and study abroad simpler.</p>
                @php
                    $socialLinks = array_filter([
                        'fb' => $siteContact['facebook_url'] ?? null,
                        'ig' => $siteContact['instagram_url'] ?? null,
                        'in' => $siteContact['linkedin_url'] ?? null,
                        'yt' => $siteContact['youtube_url'] ?? null,
                    ]);
                    $socialLabels = ['fb' => 'Facebook', 'ig' => 'Instagram', 'in' => 'LinkedIn', 'yt' => 'YouTube'];
                @endphp
                @if ($socialLinks !== [])
                    <div class="social">
                        @foreach ($socialLinks as $icon => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $socialLabels[$icon] }}"><svg class="ic"><use href="#i-{{ $icon }}"/></svg></a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <h4>Company</h4>
                <ul>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    @feature('about')<li><a href="{{ route('about') }}">About</a></li>@endfeature
                    @if (Route::has('contact'))<li><a href="{{ route('contact') }}">Contact Us</a></li>@endif
                    @feature('support')<li><a href="{{ route('support') }}">Support</a></li>@endfeature
                    <li><a href="{{ route('terms') }}">Terms</a></li>
                </ul>
            </div>

            <div class="footer__svc">
                <h4>Our Services</h4>
                <ul class="cols2">
                    @foreach ($siteNavigation as $item)
                        <li><a href="{{ $item['href'] }}">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h4>Account</h4>
                <ul>
                    @auth
                        @feature('dashboard')<li><a href="{{ route('dashboard') }}">Dashboard</a></li>@endfeature
                        @feature('account')<li><a href="{{ route('account.overview') }}">My Account</a></li>@endfeature
                        @feature('bookings')
                            @can('flights.book')<li><a href="{{ route('bookings.index') }}">My Bookings</a></li>@endcan
                        @endfeature
                    @else
                        <li><a href="{{ route('login') }}">Login</a></li>
                        <li><a href="{{ route('register') }}">Register</a></li>
                    @endauth
                </ul>
            </div>

            @if ($siteContact !== [])
                <div>
                    <h4>Contact Info</h4>
                    <ul class="contact">
                        @isset($siteContact['address'])<li><svg class="ic"><use href="#i-pin"/></svg><span>{{ $siteContact['address'] }}</span></li>@endisset
                        @isset($siteContact['phone'])<li><svg class="ic"><use href="#i-phone"/></svg><a href="tel:{{ preg_replace('/[^0-9+]/', '', $siteContact['phone']) }}">{{ $siteContact['phone'] }}</a></li>@endisset
                        @isset($siteContact['email'])<li><svg class="ic"><use href="#i-mail"/></svg><a href="mailto:{{ $siteContact['email'] }}">{{ $siteContact['email'] }}</a></li>@endisset
                        @isset($siteContact['hours'])<li><svg class="ic"><use href="#i-clock"/></svg><span>{{ $siteContact['hours'] }}</span></li>@endisset
                    </ul>
                </div>
            @endif
        </div>
        <div class="footer__bar">
            <div class="container footer__bar-in">
                <p>&copy; {{ now()->year }} Eagle Global Hub LTD. All rights reserved.</p>
                <p class="footer__note">Live supplier inventory and execution require verified production configuration.</p>
            </div>
        </div>
    </footer>

    <button class="totop" id="totop" type="button" aria-label="Back to top"><svg class="ic"><use href="#i-up"/></svg></button>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    @stack('scripts')
</body>
</html>
