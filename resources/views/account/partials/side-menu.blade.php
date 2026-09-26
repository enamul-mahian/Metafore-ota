{{--
    Customer account navigation (design: .account-layout + .side-menu).
    @param string $active  one of: overview, profile, bookings, work-visa, security
--}}
@php
    $active = $active ?? '';
    $user = auth()->user();
@endphp

<aside class="side-menu account-menu" aria-label="My account">
    <div class="account-menu__user">
        <span class="account-menu__av" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
        <span>
            <b>{{ $user->name }}</b>
            <small>{{ $user->email }}</small>
        </span>
    </div>

    @feature('account')
        <a href="{{ route('account.overview') }}" @class(['is-active' => $active === 'overview']) @if ($active === 'overview') aria-current="page" @endif>
            <svg class="ic"><use href="#i-dashboard"/></svg>Overview
        </a>
        <a href="{{ route('account.profile') }}" @class(['is-active' => $active === 'profile']) @if ($active === 'profile') aria-current="page" @endif>
            <svg class="ic"><use href="#i-user"/></svg>Profile
        </a>
    @endfeature

    @feature('bookings')
        @can('flights.book')
            <a href="{{ route('bookings.index') }}" @class(['is-active' => $active === 'bookings']) @if ($active === 'bookings') aria-current="page" @endif>
                <svg class="ic"><use href="#i-briefcase"/></svg>My Bookings
            </a>
        @endcan
    @endfeature

    @if (Route::has('work-visa.applications.index'))
        <a href="{{ route('work-visa.applications.index') }}" @class(['is-active' => $active === 'work-visa']) @if ($active === 'work-visa') aria-current="page" @endif>
            <svg class="ic"><use href="#i-file"/></svg>Work Visa Applications
        </a>
    @endif

    @feature('account')
        <a href="{{ route('account.profile') }}#security" @class(['is-active' => $active === 'security'])>
            <svg class="ic"><use href="#i-lock"/></svg>Security
        </a>
    @endfeature

    <form method="POST" action="{{ route('logout') }}" class="account-menu__logout">
        @csrf
        <button type="submit"><svg class="ic"><use href="#i-logout"/></svg>Logout</button>
    </form>
</aside>
