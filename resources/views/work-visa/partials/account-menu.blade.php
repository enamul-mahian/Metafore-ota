<nav class="side-menu" aria-label="Account">
    @feature('account')
        <a href="{{ route('account.overview') }}"><svg class="ic"><use href="#i-user"/></svg>Account Overview</a>
    @endfeature
    @feature('bookings')
        @can('flights.book')
            <a href="{{ route('bookings.index') }}"><svg class="ic"><use href="#i-plane"/></svg>My Bookings</a>
        @endcan
    @endfeature
    <a href="{{ route('work-visa.applications.index') }}" class="is-active" aria-current="page"><svg class="ic"><use href="#i-briefcase"/></svg>Work Visa Applications</a>
</nav>
