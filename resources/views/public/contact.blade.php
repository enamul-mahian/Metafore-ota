@extends('layouts.site')

@php
    $contact = $site->contact();
    $submitted = session('contact_submitted');
    $hasDirectDetails = array_intersect_key($contact, array_flip(['address', 'phone', 'whatsapp', 'email', 'hours'])) !== [];
    $socials = array_filter([
        'fb' => ['Facebook', $contact['facebook_url'] ?? null],
        'ig' => ['Instagram', $contact['instagram_url'] ?? null],
        'in' => ['LinkedIn', $contact['linkedin_url'] ?? null],
        'yt' => ['YouTube', $contact['youtube_url'] ?? null],
    ], fn (array $social): bool => $social[1] !== null);
    $faqs = [
        ['How do I contact Eagle Global Hub?', 'Send us a message with the form on this page. It goes straight to our team, and we reply to the email address or phone number you give us.'],
        ['Does sending a message or a quote request book anything?', 'No. Messages and quote requests never book, reserve or charge anything. We confirm prices, availability and conditions with you first, and you decide whether to go ahead.'],
        ['What should I include in my message?', 'Tell us where you want to go, your travel dates, how many people are travelling and, for visa questions, your passport nationality. The more detail you give, the more useful our first reply will be.'],
        ['Can I ask for a quote for a specific service?', 'Yes. Use the request forms for flights, hotels, tours, visa, holiday packages, trains, buses, cabs or travel insurance. Each form asks for the details we need for that service.'],
        ['How do you use my personal details?', 'We use the name, email and phone number you send only to answer your message or request. Payment details are never collected through this form.'],
    ];
    $agency = array_filter([
        '@type' => 'TravelAgency',
        'name' => 'Eagle Global Hub Ltd.',
        'url' => route('home'),
        'logo' => $site->imageUrl('logo'),
        'image' => $site->imageUrl('page.contact'),
        'telephone' => $contact['phone'] ?? null,
        'email' => $contact['email'] ?? null,
        'address' => isset($contact['address']) ? ['@type' => 'PostalAddress', 'streetAddress' => $contact['address']] : null,
        'hasMap' => $contact['map_url'] ?? null,
        'sameAs' => array_values(array_map(fn (array $social): string => $social[1], $socials)) ?: null,
        'contactPoint' => (isset($contact['phone']) || isset($contact['email'])) ? [array_filter([
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
            'telephone' => $contact['phone'] ?? null,
            'email' => $contact['email'] ?? null,
        ])] : null,
    ]);
@endphp

@section('og_image', $site->imageUrl('page.contact'))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Contact Us', 'item' => route('contact')],
            ],
        ],
        [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq[1]],
            ], $faqs),
        ],
        $agency,
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
@endpush

@section('content')
<section class="page-hero" style="background-image:url('{{ $site->imageUrl('page.contact') }}')">
    <div class="container page-hero__in">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>Contact Us</span></nav>
        <h1>Get In Touch</h1>
        <p>We're here to help with your flights, hotels, tours, visa and work visa questions. Send us a message and our team will get back to you.</p>
    </div>
</section>

<div class="container">
    <section class="section contact-grid">
        <div class="card">
            <h2 class="card__title">Contact Information</h2>
            <div @class(['cinfo', 'cinfo--empty' => ! $hasDirectDetails || ! isset($contact['map_url'])])>
                @if ($hasDirectDetails)
                    <ul>
                        @isset($contact['address'])
                            <li><span class="svc__ic c-blue"><svg class="ic"><use href="#i-pin"/></svg></span><div><b>Office address</b><address style="font-style:normal">{{ $contact['address'] }}</address></div></li>
                        @endisset
                        @isset($contact['phone'])
                            <li><span class="svc__ic c-green"><svg class="ic"><use href="#i-phone"/></svg></span><div><b>Phone</b><a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}" data-track="contact">{{ $contact['phone'] }}</a></div></li>
                        @endisset
                        @isset($contact['whatsapp'])
                            <li><span class="svc__ic c-green"><svg class="ic"><use href="#i-phone"/></svg></span><div><b>WhatsApp</b><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact['whatsapp']) }}" target="_blank" rel="noopener" data-track="contact">{{ $contact['whatsapp'] }}</a></div></li>
                        @endisset
                        @isset($contact['email'])
                            <li><span class="svc__ic c-amber"><svg class="ic"><use href="#i-mail"/></svg></span><div><b>Email</b><a href="mailto:{{ $contact['email'] }}" data-track="contact">{{ $contact['email'] }}</a></div></li>
                        @endisset
                        @isset($contact['hours'])
                            <li><span class="svc__ic c-indigo"><svg class="ic"><use href="#i-clock"/></svg></span><div><b>Hours</b>{{ $contact['hours'] }}</div></li>
                        @endisset
                    </ul>
                @else
                    <div class="notice-box">
                        <svg class="ic"><use href="#i-mail"/></svg>
                        <p>Send us a message using the form and our team will reply to you by email.</p>
                    </div>
                @endif

                @isset($contact['map_url'])
                    <div class="map map--link">
                        <div class="map__pin">
                            <svg class="ic"><use href="#i-pin"/></svg>
                            <a class="btn btn--white btn--sm" href="{{ $contact['map_url'] }}" target="_blank" rel="noopener">Open in Google Maps</a>
                        </div>
                    </div>
                @endisset
            </div>

            @if ($socials !== [])
                <div class="divider"></div>
                <div class="row row--between">
                    <span class="muted small">Follow us</span>
                    <div class="row" style="gap:8px">
                        @foreach ($socials as $icon => [$name, $url])
                            <a href="{{ $url }}" class="icon-btn" target="_blank" rel="noopener" aria-label="{{ $name }}"><svg class="ic"><use href="#i-{{ $icon }}"/></svg></a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="divider"></div>
            <h3 class="h3" style="margin-bottom:10px">Need a quote instead?</h3>
            <nav class="svc-links" aria-label="Request a quote">
                @foreach (collect(config('enquiries.services'))->except('other') as $key => $service)
                    <a href="{{ route('enquiries.create', ['service' => $key]) }}"><svg class="ic"><use href="#i-{{ $service['icon'] }}"/></svg>{{ $service['label'] }}</a>
                @endforeach
            </nav>
        </div>

        <div class="card" id="message">
            @if ($submitted)
                <div class="done-box" data-track-on-load="contact" role="status">
                    <span class="svc__ic c-green"><svg class="ic"><use href="#i-check"/></svg></span>
                    <h2>Thank you, your message has been sent</h2>
                    <p>Our team will read your message and reply to the email address you gave us.</p>
                    @if ($submitted['reference'])
                        <span class="done-box__ref">Message reference: #{{ $submitted['reference'] }}</span>
                    @endif
                    <div class="row">
                        <a class="btn btn--outline" href="{{ route('contact') }}#message">Send another message</a>
                    </div>
                </div>
            @else
                <h2 class="card__title">Send Us a Message</h2>
                @if ($errors->any())
                    <div class="notice-box notice-box--error" role="alert" style="margin-bottom:14px">
                        <svg class="ic"><use href="#i-x"/></svg>
                        <div><b>Please check the highlighted fields.</b></div>
                    </div>
                @endif
                <form method="POST" action="{{ route('contact.store') }}" class="form-grid form-grid--2">
                    @csrf
                    <input type="hidden" name="source_url" value="{{ url()->current() }}">

                    @include('partials.site.contact-fields', ['phoneFull' => true])

                    <div @class(['field', 'span-all', 'field--error' => $errors->has('subject')])>
                        <label for="c-subject">Subject <em>*</em></label>
                        <input class="input" id="c-subject" name="subject" value="{{ old('subject') }}" required maxlength="150" list="c-subjects" placeholder="How can we help?" autocomplete="off">
                        <datalist id="c-subjects">
                            @foreach (['Flight booking', 'Hotel booking', 'Tour or holiday package', 'Tourist visa', 'Work visa', 'Change or cancel a booking', 'Other question'] as $subject)
                                <option value="{{ $subject }}">
                            @endforeach
                        </datalist>
                        @error('subject')<span class="field__error">{{ $message }}</span>@enderror
                    </div>

                    <div @class(['field', 'span-all', 'field--error' => $errors->has('message')])>
                        <label for="c-message">Your message <em>*</em></label>
                        <textarea class="input" id="c-message" name="message" required maxlength="3000" placeholder="Tell us your travel dates, destination and number of travellers">{{ old('message') }}</textarea>
                        @error('message')<span class="field__error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-actions span-all">
                        <button type="submit" class="btn btn--blue btn--lg btn--block"><svg class="ic"><use href="#i-send"/></svg>Send Message</button>
                        <p class="secure" style="margin-top:0"><svg class="ic"><use href="#i-lock"/></svg>We only use your details to reply to your message.</p>
                    </div>
                </form>
            @endif
        </div>
    </section>

    <section class="section" id="faq">
        <div class="section__head"><div><h2>Frequently Asked Questions</h2><p>How contacting us and requesting a quote works.</p></div></div>
        <div class="faq">
            @foreach ($faqs as $index => [$question, $answer])
                <details @if ($index === 0) open @endif><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
            @endforeach
        </div>
    </section>
    <div style="height:48px"></div>
</div>
@endsection
