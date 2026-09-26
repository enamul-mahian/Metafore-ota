@extends('layouts.site')

@section('body_class', 'page-home')

@php
    $user = auth()->user();
    $enquiryUrl = fn (string $service, array $query = []): ?string => Route::has('enquiries.create')
        ? route('enquiries.create', ['service' => $service] + $query)
        : null;

    $serviceIsLive = fn (string $key): bool => (bool) ($travelServices[$key]['available'] ?? false)
        && Route::has($travelServices[$key]['route_name'] ?? '');

    $features = app(\App\Services\Feature\FeatureManager::class);
    $isVisible = fn (string $key): bool => ! $features->isRegistered($key) || $features->isVisibleTo($key, $user);

    $flightsLive = $isVisible('flights') && $serviceIsLive('flights') && (! $user || $user->can('flights.search'));

    $searchTabs = [
        'flights' => ['label' => 'Flights', 'icon' => 'plane', 'feature' => 'flights'],
        'hotels' => ['label' => 'Hotels', 'icon' => 'hotel', 'feature' => 'hotels'],
        'tours' => ['label' => 'Tours', 'icon' => 'palm', 'feature' => 'tours'],
        'visa' => ['label' => 'Visa', 'icon' => 'passport', 'feature' => 'visa'],
        'work-visa' => ['label' => 'Work Visa', 'icon' => 'briefcase', 'feature' => null],
        'packages' => ['label' => 'Holiday Packages', 'icon' => 'gift', 'feature' => null],
        'trains' => ['label' => 'Trains', 'icon' => 'train', 'feature' => null],
        'buses' => ['label' => 'Buses', 'icon' => 'bus', 'feature' => null],
        'cabs' => ['label' => 'Cabs', 'icon' => 'car', 'feature' => null],
        'insurance' => ['label' => 'Insurance', 'icon' => 'shield', 'feature' => null],
    ];

    $searchTabs = collect($searchTabs)
        ->filter(fn (array $tab, string $key): bool => $isVisible($key))
        ->filter(fn (array $tab, string $key): bool => match ($key) {
            'work-visa' => Route::has('work-visa.index'),
            default => $enquiryUrl($key) !== null || $serviceIsLive($key),
        })
        ->all();

    $firstTab = array_key_first($searchTabs);
    $today = now()->toDateString();

    $services = [
        ['key' => 'flights', 'label' => 'Flights', 'hint' => 'Domestic & international', 'icon' => 'plane', 'color' => 'c-blue'],
        ['key' => 'hotels', 'label' => 'Hotels', 'hint' => 'Stays worldwide', 'icon' => 'hotel', 'color' => 'c-red'],
        ['key' => 'tours', 'label' => 'Tours & Activities', 'hint' => 'Things to do', 'icon' => 'palm', 'color' => 'c-green'],
        ['key' => 'visa', 'label' => 'Visa', 'hint' => 'Tourist & business visa', 'icon' => 'passport', 'color' => 'c-indigo'],
        ['key' => 'work-visa', 'label' => 'Work Visa Processing', 'hint' => 'Jobs & work permits', 'icon' => 'briefcase', 'color' => 'c-amber'],
        ['key' => 'packages', 'label' => 'Holiday Packages', 'hint' => 'Tailor-made trips', 'icon' => 'gift', 'color' => 'c-red'],
        ['key' => 'trains', 'label' => 'Trains', 'hint' => 'Ticket assistance', 'icon' => 'train', 'color' => 'c-blue'],
        ['key' => 'buses', 'label' => 'Buses', 'hint' => 'Ticket assistance', 'icon' => 'bus', 'color' => 'c-sky'],
        ['key' => 'cabs', 'label' => 'Cabs', 'hint' => 'Airport & city rides', 'icon' => 'car', 'color' => 'c-orange'],
        ['key' => 'insurance', 'label' => 'Travel Insurance', 'hint' => 'Cover for your trip', 'icon' => 'shield', 'color' => 'c-green'],
    ];

    $serviceLink = function (string $key) use ($travelServices, $serviceIsLive, $enquiryUrl, $isVisible, $flightsLive): ?string {
        if (! $isVisible($key)) {
            return null;
        }

        if ($key === 'flights') {
            return $flightsLive ? route('flights.index') : $enquiryUrl('flights');
        }

        if (isset($travelServices[$key])) {
            return $serviceIsLive($key) ? route($travelServices[$key]['route_name']) : $enquiryUrl($key);
        }

        return match ($key) {
            'work-visa' => Route::has('work-visa.index') ? route('work-visa.index') : null,
            'packages' => Route::has('packages.index') ? route('packages.index') : $enquiryUrl('packages'),
            default => $enquiryUrl($key),
        };
    };

    $destinations = $site->destinations(true);
    $contact = $site->contact();
@endphp

@push('head')
    <link rel="preload" as="image" href="{{ $site->imageUrl('home.hero') }}">
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'TravelAgency',
            'name' => 'Eagle Global Hub',
            'url' => route('home'),
            'logo' => $site->imageUrl('logo'),
            'image' => $site->imageUrl('home.hero'),
            'telephone' => $contact['phone'] ?? null,
            'email' => $contact['email'] ?? null,
            'address' => isset($contact['address']) ? ['@type' => 'PostalAddress', 'streetAddress' => $contact['address'], 'addressLocality' => 'Dhaka', 'addressCountry' => 'BD'] : null,
            'sameAs' => array_values(array_filter([$contact['facebook_url'] ?? null, $contact['instagram_url'] ?? null, $contact['linkedin_url'] ?? null, $contact['youtube_url'] ?? null])) ?: null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
@endpush

@section('content')
{{-- ============ HERO + SEARCH ============ --}}
<section class="hero">
    <div class="hero__bg" style="background-image:url('{{ $site->imageUrl('home.hero') }}')"></div>
    <div class="container hero__in">
        <div class="hero__copy">
            <p class="hero__eyebrow">Explore · Book · Travel · Grow</p>
            <h1>Travel the World with<br><span>Eagle Global Hub</span></h1>
            <p class="hero__sub">Flights, Hotels, Tours, Visa, Work Visa Processing &amp; More<br>Your global travel and visa partner in Dhaka</p>
        </div>
        <p class="hero__script" aria-hidden="true">Discover<br>Explore<br>Work<br>Study<br>Travel Beyond<br>Boundaries</p>
    </div>

    @if ($searchTabs !== [])
        <div class="container">
            <div class="search" id="search" data-search-widget>
                <div class="search__tabs" role="tablist" aria-label="What are you looking for?">
                    @foreach ($searchTabs as $key => $tab)
                        <button type="button" role="tab" @class(['stab', 'is-active' => $key === $firstTab]) data-search-tab="{{ $key }}" aria-selected="{{ $key === $firstTab ? 'true' : 'false' }}" aria-controls="search-{{ $key }}">
                            <svg class="ic"><use href="#i-{{ $tab['icon'] }}"/></svg>{{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>

                <div class="search__body">
                    @foreach ($searchTabs as $key => $tab)
                        <div id="search-{{ $key }}" role="tabpanel" data-search-panel="{{ $key }}" @if ($key !== $firstTab) hidden @endif>
                            @include('partials.home.search-'.(in_array($key, ['flights', 'work-visa'], true) ? $key : 'enquiry'), [
                                'serviceKey' => $key,
                                'serviceLabel' => $tab['label'],
                                'isLive' => $key === 'flights' ? $flightsLive : $serviceIsLive($key),
                                'liveUrl' => isset($travelServices[$key]) && $serviceIsLive($key) ? route($travelServices[$key]['route_name']) : null,
                                'enquiryUrl' => $enquiryUrl($key),
                                'today' => $today,
                            ])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</section>

{{-- ============ FEATURES STRIP ============ --}}
<section class="strip" aria-label="Why book with us">
    <div class="container strip__in">
        <div class="strip__item"><span class="ring"><svg class="ic"><use href="#i-lock"/></svg></span><div><b>Secure Booking</b><small>Protected account &amp; checkout</small></div></div>
        <div class="strip__item"><span class="ring"><svg class="ic"><use href="#i-handshake"/></svg></span><div><b>Easy Requests</b><small>Ask for any trip in minutes</small></div></div>
        <div class="strip__item"><span class="ring"><svg class="ic"><use href="#i-file"/></svg></span><div><b>Document Help</b><small>Visa &amp; work permit papers</small></div></div>
        <div class="strip__item"><span class="ring"><svg class="ic"><use href="#i-bell"/></svg></span><div><b>Booking Updates</b><small>Track status in your account</small></div></div>
        <div class="strip__item"><span class="ring"><svg class="ic"><use href="#i-globe"/></svg></span><div><b>Global Trips</b><small>Flights, stays &amp; tours abroad</small></div></div>
    </div>
</section>

{{-- ============ PROMOS ============ --}}
<section class="container promos" id="offers" aria-label="Highlights">
    <article class="promo promo--sky">
        <div class="promo__txt">
            <h2 class="promo__title">International Flights</h2>
            <p class="promo__lead">Compare fares from Dhaka to the world</p>
            @if ($link = $serviceLink('flights'))
                <a href="{{ $link }}" class="btn btn--blue btn--sm">{{ $flightsLive ? 'Search Flights' : 'Get a Fare Quote' }} <svg class="ic"><use href="#i-arrow-r"/></svg></a>
            @endif
        </div>
        <img src="{{ $site->imageUrl('home.promo_flights') }}" alt="Airplane in flight" class="promo__img" width="600" height="400">
    </article>
    <article class="promo promo--sand">
        <div class="promo__txt">
            <h2 class="promo__title promo__big--dark">Hotel Bookings</h2>
            <p class="promo__lead dark"><b>Stays for every budget</b></p>
            <p class="promo__small">City hotels, resorts and apartments</p>
            @if ($link = $serviceLink('hotels'))
                <a href="{{ $link }}" class="btn btn--white">Book a Hotel <svg class="ic"><use href="#i-arrow-r"/></svg></a>
            @endif
        </div>
        <img src="{{ $site->imageUrl('home.promo_hotels') }}" alt="Tropical resort with pool" class="promo__img promo__img--fade-sand" width="600" height="400">
    </article>
    <article class="promo promo--navy">
        <div class="promo__txt">
            <h2 class="promo__title">Work Visa Processing</h2>
            <p class="promo__lead">Start your global career</p>
            <ul class="ticks ticks--light"><li>Job visa</li><li>Skilled worker visa</li><li>Employer sponsored visa</li><li>Document assistance</li></ul>
            @if ($link = $serviceLink('work-visa'))
                <a href="{{ $link }}" class="btn btn--white">Apply Now <svg class="ic"><use href="#i-arrow-r"/></svg></a>
            @endif
        </div>
        <img src="{{ $site->imageUrl('home.promo_work_visa') }}" alt="Professional holding a passport" class="promo__img promo__img--fade-navy" width="600" height="400">
    </article>
</section>

{{-- ============ SERVICES ============ --}}
<section class="container section" id="services">
    <div class="section__head"><div><h2>Our Services</h2><p>Travel services and their current status</p></div></div>
    <div class="services">
        @foreach ($services as $service)
            @php
                $registry = $travelServices[$service['key']] ?? null;
                $visible = $isVisible($service['key']);
                $link = $visible ? $serviceLink($service['key']) : null;
                $status = $registry ? $registry['status'] : ($service['key'] === 'work-visa' || $service['key'] === 'packages' ? 'Available' : 'On request');
            @endphp
            @continue(! $visible)
            @if ($link)
                <a href="{{ $link }}" class="svc">
            @else
                <div class="svc">
            @endif
                <span class="svc__ic {{ $service['color'] }}"><svg class="ic"><use href="#i-{{ $service['icon'] }}"/></svg></span>
                <span>
                    <b>{{ $service['label'] }}</b>
                    <small>{{ $service['hint'] }}</small>
                    <em @class(['svc__status', 'is-live' => $status === 'Available'])>{{ $status }}</em>
                </span>
            @if ($link)
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>
</section>

{{-- ============ DESTINATIONS ============ --}}
@if ($destinations !== [])
    <section class="container section" id="destinations">
        <div class="section__head">
            <div><h2>Popular Destinations</h2><p>Places our travellers ask about most</p></div>
            <div class="section__ctrl">
                <button type="button" class="round" data-scroll="dest" data-dir="-1" aria-label="Previous destinations"><svg class="ic"><use href="#i-chev-l"/></svg></button>
                <button type="button" class="round" data-scroll="dest" data-dir="1" aria-label="Next destinations"><svg class="ic"><use href="#i-chev-r"/></svg></button>
            </div>
        </div>
        <div class="dest" id="dest">
            @foreach ($destinations as $destination)
                @php
                    $destinationUrl = Route::has('packages.index')
                        ? route('packages.index', ['destination' => $destination['name']])
                        : $enquiryUrl('packages', ['destination' => $destination['name']]);
                    $destinationImage = $site->destinationImageUrl($destination['image']);
                @endphp
                <a href="{{ $destinationUrl ?? '#' }}" class="dest__card">
                    @if ($destinationImage !== '')
                        <img src="{{ $destinationImage }}" alt="{{ $destination['name'] }}{{ $destination['country'] ? ', '.$destination['country'] : '' }}" loading="lazy" width="300" height="340">
                    @endif
                    <span><b>{{ $destination['name'] }}</b>{{ $destination['country'] }}</span>
                    <i>Plan a trip</i>
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- ============ FEATURE BANNERS ============ --}}
<section class="container banners" aria-label="Visa, work visa and holidays">
    <article class="banner banner--sky">
        <div class="banner__txt">
            <h2 class="h3">Visa Services</h2>
            <p>Check requirements and get help with your application</p>
            <ul class="ticks"><li>Tourist visa</li><li>Business visa</li><li>Transit visa</li><li>Requirements check</li></ul>
            @if ($link = $serviceLink('visa'))
                <a href="{{ $link }}" class="btn btn--blue">Get Visa Help <svg class="ic"><use href="#i-arrow-r"/></svg></a>
            @endif
        </div>
        <img src="{{ $site->imageUrl('home.banner_visa') }}" alt="Passports and a globe" class="banner__img" loading="lazy" width="400" height="300">
    </article>
    <article class="banner banner--warm">
        <div class="banner__txt">
            <h2 class="h3 red">Work Visa Processing</h2>
            <p>Your global career starts here</p>
            <ul class="ticks ticks--orange"><li>Job visa &amp; work permits</li><li>Skilled worker visa</li><li>Employer sponsored visa</li><li>Document assistance</li></ul>
            @if ($link = $serviceLink('work-visa'))
                <a href="{{ $link }}" class="btn btn--blue">Apply Now <svg class="ic"><use href="#i-arrow-r"/></svg></a>
            @endif
        </div>
        <img src="{{ $site->imageUrl('home.banner_work_visa') }}" alt="Skilled professionals" class="banner__img" loading="lazy" width="400" height="300">
    </article>
    <article class="banner banner--sea">
        <div class="banner__txt">
            <h2 class="h3 green">Holiday Packages</h2>
            <p>Trips planned around you</p>
            <ul class="ticks ticks--green"><li>Family packages</li><li>Honeymoon packages</li><li>Adventure tours</li><li>Custom itineraries</li></ul>
            @if ($link = $serviceLink('packages'))
                <a href="{{ $link }}" class="btn btn--blue">View Packages <svg class="ic"><use href="#i-arrow-r"/></svg></a>
            @endif
        </div>
        <img src="{{ $site->imageUrl('home.banner_holiday') }}" alt="Beach bungalows" class="banner__img" loading="lazy" width="400" height="300">
    </article>
</section>

{{-- ============ WHY US ============ --}}
<section class="container section">
    <div class="section__head"><div><h2>Why Choose Eagle Global Hub?</h2></div></div>
    <div class="why">
        <div class="why__item"><span class="ring"><svg class="ic"><use href="#i-users"/></svg></span><div><b>One Account</b><small>Flights, stays, visas &amp; more</small></div></div>
        <div class="why__item"><span class="ring"><svg class="ic"><use href="#i-tag"/></svg></span><div><b>Clear Prices</b><small>Totals shown before you pay</small></div></div>
        <div class="why__item"><span class="ring"><svg class="ic"><use href="#i-card"/></svg></span><div><b>Secure Payment</b><small>Checked before confirmation</small></div></div>
        <div class="why__item"><span class="ring"><svg class="ic"><use href="#i-headset"/></svg></span><div><b>Real People</b><small>Our Dhaka team replies</small></div></div>
        <div class="why__item"><span class="ring"><svg class="ic"><use href="#i-passport"/></svg></span><div><b>Visa Know-how</b><small>Tourist, business &amp; work</small></div></div>
        <div class="why__item"><span class="ring"><svg class="ic"><use href="#i-file"/></svg></span><div><b>Paperwork Help</b><small>We guide every document</small></div></div>
    </div>
</section>

{{-- ============ NEWSLETTER ============ --}}
@if (Route::has('newsletter.store'))
    <section class="cta" style="background-image:url('{{ $site->imageUrl('home.newsletter') }}')">
        <div class="container cta__in">
            <div>
                <h2 class="h3">Let's Make Your<br>Next Journey Amazing</h2>
                <p>Travel deals and visa news, straight to your inbox.</p>
                @if (session('newsletter_subscribed'))
                    <p class="cta__done" role="status" data-track-on-load="subscribe">{{ session('newsletter_subscribed') }}</p>
                @endif
                @error('email', 'newsletter')
                    <p class="cta__error" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <form class="subscribe" method="POST" action="{{ route('newsletter.store') }}" aria-label="Newsletter sign-up" id="newsletter">
                @csrf
                <input type="hidden" name="source" value="home">
                <div class="hp-field" aria-hidden="true"><label for="newsletter-website">Leave empty</label><input id="newsletter-website" name="{{ \App\Http\Requests\StoreEnquiryRequest::HONEYPOT }}" tabindex="-1" autocomplete="off"></div>
                <label for="newsletter-email" class="sr">Email address</label>
                <input id="newsletter-email" name="email" type="email" inputmode="email" autocomplete="email" placeholder="Enter your email address" required maxlength="190">
                <button class="btn btn--blue" type="submit"><svg class="ic"><use href="#i-send"/></svg>Subscribe</button>
            </form>
        </div>
    </section>
@endif
@endsection
