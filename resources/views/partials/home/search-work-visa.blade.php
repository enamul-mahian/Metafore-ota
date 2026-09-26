{{-- Homepage work visa tab: points to the work visa module. --}}
<div class="search__cta">
    <div>
        <b>Work abroad with the right visa</b>
        <p>Check countries, required documents and apply online. Track your application from your account.</p>
    </div>
    <div class="search__cta-actions">
        <a href="{{ route('work-visa.index') }}" class="btn btn--outline">See Countries</a>
        @if (Route::has('work-visa.apply'))
            <a href="{{ route('work-visa.apply') }}" class="search__go"><svg class="ic"><use href="#i-briefcase"/></svg>Apply Now</a>
        @endif
    </div>
</div>
