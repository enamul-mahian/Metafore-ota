{{--
    Newsletter sign-up. Include with ['source' => 'footer'|'home'|...].
    After a successful submit the page shows a confirmation that fires the
    "subscribe" tracking event.
--}}
@php
    $newsletterSource = $source ?? 'website';
    $newsletterErrors = $errors->getBag('newsletter');
@endphp
<div class="news-form" id="newsletter">
    @if (session('newsletter_subscribed'))
        <p class="news-done" data-track-on-load="subscribe" role="status"><svg class="ic"><use href="#i-check"/></svg>{{ session('newsletter_subscribed') }}</p>
    @else
        <form method="POST" action="{{ route('newsletter.store') }}" class="subscribe">
            @csrf
            <input type="hidden" name="source" value="{{ $newsletterSource }}">
            <label class="sr" for="newsletter-email-{{ $newsletterSource }}">Email address</label>
            <input id="newsletter-email-{{ $newsletterSource }}" type="email" name="email" value="{{ old('email') && $newsletterErrors->any() ? old('email') : '' }}" placeholder="Enter your email address" autocomplete="email" inputmode="email" required maxlength="255">
            <span class="hp-field" aria-hidden="true"><input type="text" name="website" value="" tabindex="-1" autocomplete="off"></span>
            <button type="submit" class="btn btn--blue">Subscribe</button>
        </form>
        @if ($newsletterErrors->any())
            <p class="news-error" role="alert">{{ $newsletterErrors->first() }}</p>
        @endif
    @endif
</div>
