@extends('layouts.site')

@include('partials.site.route-seo', ['seoRoute' => 'visa.index'])

@section('og_image', $site->imageUrl('page.visa'))

@php
    $visaServices = [
        ['Tourist', 'Tourist Visa', 'Holidays & family visits', 'palm', 'c-green'],
        ['Business', 'Business Visa', 'Meetings, fairs & trade', 'briefcase', 'c-blue'],
        ['Transit', 'Transit Visa', 'Airport stopovers', 'plane', 'c-orange'],
        ['Student', 'Student Visa', 'Study abroad', 'bank', 'c-indigo'],
        ['Medical', 'Medical Visa', 'Treatment abroad', 'shield', 'c-sky'],
    ];
@endphp

@section('content')
<section class="split-hero">
    <div class="split-hero__img" style="background-image:url('{{ $site->imageUrl('page.visa') }}')" role="img" aria-label="Passport, visa stamps and travel documents"></div>
    <div class="container split-hero__in">
        <div class="split-hero__copy">
            <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>Visa</span></nav>
            <h1>Visa Requirements</h1>
            <p class="split-hero__sub">Check before you travel</p>
            <p class="lead">Check travel-document information from a configured source using your passport nationality and actual journey details. Approval and entry are never guaranteed.</p>
        </div>
    </div>
</section>

<div class="container">
    <div class="section">
        @if (! $service['available'])
            @include('partials.site.service-unavailable', [
                'icon' => 'passport',
                'badge' => 'Not Configured',
                'title' => 'Visa information service is not configured',
                'text' => 'Visa requirements will be available only after an approved information provider and its required server configuration are enabled.',
                'service' => 'visa',
            ])
        @elseif ($countries->isEmpty())
            @include('partials.site.service-unavailable', [
                'icon' => 'globe',
                'badge' => 'Unavailable',
                'tone' => 'muted',
                'title' => 'Country information is unavailable',
                'text' => 'Visa lookup cannot continue until the active country catalogue is available.',
                'service' => 'visa',
            ])
        @else
            <section class="card" id="checker" aria-labelledby="visa-check-title">
                <h2 class="card__title" id="visa-check-title"><span>Check your trip requirements</span><svg class="ic"><use href="#i-passport"/></svg></h2>
                <form method="POST" action="{{ route('visa.requirements') }}" class="form-grid" data-track="search">
                    @csrf
                    @foreach (['nationality' => 'Passport nationality', 'origin_country' => 'Origin country', 'destination_country' => 'Destination country'] as $name => $label)
                        <div @class(['field', 'field--error' => $errors->has($name)])>
                            <label for="v-{{ $name }}">{{ $label }}</label>
                            <select class="input" id="v-{{ $name }}" name="{{ $name }}" required autocomplete="{{ $name === 'nationality' ? 'country' : 'off' }}">
                                <option value="">Select country</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->iso3 }}" @selected(old($name) === $country->iso3)>{{ $country->name }}</option>
                                @endforeach
                            </select>
                            @error($name)<span class="field__error">{{ $message }}</span>@enderror
                        </div>
                    @endforeach
                    <div @class(['field', 'field--error' => $errors->has('departure_date')])>
                        <label for="v-departure-date">Departure date</label>
                        <input class="input" id="v-departure-date" type="date" name="departure_date" value="{{ old('departure_date') }}" min="{{ now()->toDateString() }}" required>
                        @error('departure_date')<span class="field__error">{{ $message }}</span>@enderror
                    </div>
                    <div @class(['field', 'field--error' => $errors->has('departure_time')])>
                        <label for="v-departure-time">Departure time</label>
                        <input class="input" id="v-departure-time" type="time" name="departure_time" value="{{ old('departure_time') }}" required>
                        @error('departure_time')<span class="field__error">{{ $message }}</span>@enderror
                    </div>
                    <div @class(['field', 'field--error' => $errors->has('arrival_date')])>
                        <label for="v-arrival-date">Arrival date</label>
                        <input class="input" id="v-arrival-date" type="date" name="arrival_date" value="{{ old('arrival_date') }}" min="{{ now()->toDateString() }}" required>
                        @error('arrival_date')<span class="field__error">{{ $message }}</span>@enderror
                    </div>
                    <div @class(['field', 'field--error' => $errors->has('arrival_time')])>
                        <label for="v-arrival-time">Arrival time</label>
                        <input class="input" id="v-arrival-time" type="time" name="arrival_time" value="{{ old('arrival_time') }}" required>
                        @error('arrival_time')<span class="field__error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field span-2">
                        <span class="hide-sm" aria-hidden="true">&nbsp;</span>
                        <button type="submit" class="btn btn--blue btn--block">Check Requirements <svg class="ic"><use href="#i-arrow-r"/></svg></button>
                    </div>
                </form>
                <p class="hint" style="margin-top:12px">This service provides travel-requirement information only. It does not guarantee visa approval or admission at the border.</p>
            </section>
        @endif
    </div>

    <section class="section" id="types">
        <div class="section__head"><div><h2>Visa Services We Handle</h2><p>Tell us your passport and destination and our team explains the documents and next steps.</p></div></div>
        <div class="tiles tiles--6">
            @foreach ($visaServices as [$value, $label, $caption, $icon, $color])
                <a href="{{ route('enquiries.create', ['service' => 'visa', 'visa_type' => $value]) }}" class="tile"><span class="svc__ic {{ $color }}"><svg class="ic"><use href="#i-{{ $icon }}"/></svg></span>{{ $label }}<small>{{ $caption }}</small></a>
            @endforeach
            @if (Route::has('work-visa.index'))
                <a href="{{ route('work-visa.index') }}" class="tile"><span class="svc__ic c-red"><svg class="ic"><use href="#i-users"/></svg></span>Work Visa<small>Jobs &amp; work permits</small></a>
            @endif
        </div>
    </section>

    <section class="section">
        <div class="section__head"><div><h2>How Our Visa Help Works</h2><p>Clear steps, with the final decision always made by the embassy.</p></div></div>
        <ol class="process process--4">
            <li><b>Check requirements</b><p>Use the checker when it is available, or send us your trip details.</p></li>
            <li><b>Request assistance</b><p>Tell us your passport nationality, destination and travel dates.</p></li>
            <li><b>Prepare documents</b><p>Our team explains the documents and forms needed for your application.</p></li>
            <li><b>Embassy decision</b><p>The embassy or immigration authority reviews and decides on your application.</p></li>
        </ol>
    </section>

    @include('visa._faq')

    <div class="cta-band">
        <div><h2>Need help with your visa file?</h2><p>Send us your trip details and a visa specialist will get back to you.</p></div>
        <a href="{{ route('enquiries.create', ['service' => 'visa']) }}" class="btn btn--white">Request Visa Assistance <svg class="ic"><use href="#i-arrow-r"/></svg></a>
    </div>
    <div style="height:48px"></div>
</div>
@endsection
