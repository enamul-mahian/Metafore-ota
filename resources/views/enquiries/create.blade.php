@extends('layouts.site')

@php
    $submitted = session('enquiry_submitted');
    $label = $definition['label'];
    $contact = $site->contact();
    $otherServices = collect(config('enquiries.services'))->except([$service, 'other']);
@endphp

@section('seo_title', $definition['title'].' | Eagle Global Hub')
@section('seo_description', 'Send a '.mb_strtolower($label).' request to Eagle Global Hub. Share your trip details and our Bangladesh travel team replies with options and prices. Free to ask.')
@section('og_image', $site->imageUrl($definition['image']))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $definition['title'], 'item' => route('enquiries.create', ['service' => $service])],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
@endpush

@section('content')
<section class="page-hero page-hero--svc" style="background-image:url('{{ $site->imageUrl($definition['image']) }}')">
    <div class="container page-hero__in">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a>@if ($service === 'packages' && Route::has('packages.index'))<span><a href="{{ route('packages.index') }}">Holiday Packages</a></span>@endif<span>{{ $definition['title'] }}</span></nav>
        <h1>{{ $definition['title'] }}</h1>
        <p>{{ $definition['intro'] }}</p>
    </div>
</section>

<div class="container page">
    <div class="layout-side">
        <div class="card" id="request">
            @if ($submitted)
                <div class="done-box" data-track-on-load="lead" role="status">
                    <span class="svc__ic c-green"><svg class="ic"><use href="#i-check"/></svg></span>
                    <h2>Thank you, your request has been sent</h2>
                    <p>Our team will review your request and reply to the email or phone number you gave us. Nothing has been booked or charged.</p>
                    @if ($submitted['reference'])
                        <span class="done-box__ref">Request reference: #{{ $submitted['reference'] }}</span>
                    @endif
                    <div class="row">
                        <a class="btn btn--outline" href="{{ route('enquiries.create', ['service' => $service]) }}">Send another request</a>
                        <a class="btn btn--blue" href="{{ route('home') }}">Back to home</a>
                    </div>
                </div>
            @else
                <h2 class="card__title"><span>Your request details</span><svg class="ic"><use href="#i-{{ $definition['icon'] }}"/></svg></h2>

                @if ($errors->any())
                    <div class="notice-box notice-box--error" role="alert" style="margin-bottom:14px">
                        <svg class="ic"><use href="#i-x"/></svg>
                        <div><b>Please check the highlighted fields.</b></div>
                    </div>
                @endif

                <form method="POST" action="{{ route('enquiries.store', ['service' => $service]) }}" class="form-grid form-grid--2">
                    @csrf
                    <input type="hidden" name="source_url" value="{{ old('source_url', $sourceUrl) }}">

                    @foreach ($definition['fields'] as $key => $field)
                        @include('enquiries._field', [
                            'key' => $key,
                            'field' => $field,
                            'value' => old($key, $prefill[$key] ?? ($field['default'] ?? '')),
                        ])
                    @endforeach

                    <h3 class="form-sec span-all"><svg class="ic"><use href="#i-user"/></svg>Your contact details</h3>

                    @include('partials.site.contact-fields', ['phoneFull' => true])

                    <div @class(['field', 'span-all', 'field--error' => $errors->has('message')])>
                        <label for="c-message">Anything else we should know?</label>
                        <textarea class="input" id="c-message" name="message" maxlength="3000" placeholder="Preferred airline, hotel area, special requests, flexible dates…">{{ old('message', $prefill['message'] ?? '') }}</textarea>
                        @error('message')<span class="field__error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-actions span-all">
                        <button type="submit" class="btn btn--blue btn--lg btn--block"><svg class="ic"><use href="#i-send"/></svg>Send Request</button>
                        <p class="secure" style="margin-top:0"><svg class="ic"><use href="#i-lock"/></svg>We only use your details to reply to this request.</p>
                    </div>
                </form>
            @endif
        </div>

        <aside class="stack" aria-label="About this request">
            <div class="card">
                <h2 class="card__title">What happens next</h2>
                <ol class="next-steps">
                    <li><div><b>We receive your request</b><p>Your details are saved for our team. Sending a request does not book or charge anything.</p></div></li>
                    <li><div><b>We check the options</b><p>Our team checks availability, prices and rules with the relevant airlines, hotels, operators or embassies.</p></div></li>
                    <li><div><b>We get back to you</b><p>We reply by email or phone with what we found. You decide whether to go ahead before anything is booked or paid.</p></div></li>
                </ol>
            </div>

            @if (isset($contact['phone']) || isset($contact['whatsapp']) || isset($contact['email']))
                <div class="card">
                    <h2 class="card__title">Prefer to talk?</h2>
                    <ul class="stack" style="gap:10px;font-size:13.5px">
                        @isset($contact['phone'])<li class="row" style="gap:8px"><svg class="ic"><use href="#i-phone"/></svg><a class="link" href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}" data-track="contact">{{ $contact['phone'] }}</a></li>@endisset
                        @isset($contact['whatsapp'])<li class="row" style="gap:8px"><svg class="ic"><use href="#i-phone"/></svg><a class="link" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact['whatsapp']) }}" target="_blank" rel="noopener" data-track="contact">WhatsApp {{ $contact['whatsapp'] }}</a></li>@endisset
                        @isset($contact['email'])<li class="row" style="gap:8px"><svg class="ic"><use href="#i-mail"/></svg><a class="link" href="mailto:{{ $contact['email'] }}" data-track="contact">{{ $contact['email'] }}</a></li>@endisset
                        @isset($contact['hours'])<li class="row muted" style="gap:8px"><svg class="ic"><use href="#i-clock"/></svg>{{ $contact['hours'] }}</li>@endisset
                    </ul>
                </div>
            @endif

            <div class="card">
                <h2 class="card__title">Other requests</h2>
                <nav class="svc-links" aria-label="Other services">
                    @foreach ($otherServices as $key => $other)
                        <a href="{{ route('enquiries.create', ['service' => $key]) }}"><svg class="ic"><use href="#i-{{ $other['icon'] }}"/></svg>{{ $other['label'] }}</a>
                    @endforeach
                </nav>
            </div>
        </aside>
    </div>
</div>
@endsection
