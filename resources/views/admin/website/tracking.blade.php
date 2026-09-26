@extends('layouts.admin')

@section('title', 'Tracking & Ads')

@section('content')
<x-admin.page-header title="Tracking & Ads" description="Connect Google Analytics, Google Ads, Google Tag Manager, Meta Pixel, TikTok or any other ad and analytics service." icon="W" eyebrow="Website" />

@include('admin.website._tabs')

<form method="POST" action="{{ route('admin.website.tracking.update') }}" class="egh-card wsc-section">
    @csrf
    @method('PUT')
    <h2 class="wsc-section__title">Connected services</h2>
    <p class="wsc-muted">Paste only the ID. The website adds the official tag to every public page and sends search, lead, checkout and purchase events automatically.</p>

    <div class="wsc-form-grid">
        @foreach ($trackingFields as $field => $definition)
            <label for="tracking-{{ $field }}">
                <span class="wsc-label-row">{{ $definition['label'] }}
                    @if (($tracking[$field] ?? '') !== '')<em class="wsc-badge is-custom">Connected</em>@endif
                </span>
                <input id="tracking-{{ $field }}" name="{{ $field }}" value="{{ old($field, $tracking[$field] ?? '') }}" placeholder="{{ $definition['placeholder'] }}" autocomplete="off" spellcheck="false" @disabled(! $canManage)>
            </label>
        @endforeach
    </div>

    @if ($canManage)
        <div class="admin-form-actions">
            <button type="submit" class="egh-button">Save tracking</button>
        </div>
    @endif
</form>

<section class="egh-card wsc-section">
    <h2 class="wsc-section__title">Other ad or analytics services (custom code)</h2>
    <p class="wsc-muted">For any service without a field above (Microsoft Clarity, Hotjar, LinkedIn Insight, Snapchat, Taboola, affiliate networks…), paste the snippet it gives you. It runs on every public page, so only super admins can change it.</p>

    @role('super-admin')
        <form method="POST" action="{{ route('admin.website.custom-code.update') }}">
            @csrf
            @method('PUT')
            <label for="custom-head" class="wsc-code-label">Code inside &lt;head&gt;
                <textarea id="custom-head" name="head" rows="7" class="wsc-code" spellcheck="false">{{ old('head', $customCode['head']) }}</textarea>
            </label>
            <label for="custom-body" class="wsc-code-label">Code at the start of &lt;body&gt;
                <textarea id="custom-body" name="body" rows="5" class="wsc-code" spellcheck="false">{{ old('body', $customCode['body']) }}</textarea>
            </label>
            <div class="admin-form-actions">
                <button type="submit" class="egh-button">Save custom code</button>
            </div>
        </form>
    @else
        <p class="wsc-muted">{{ $customCode['head'] !== '' || $customCode['body'] !== '' ? 'Custom code is active.' : 'No custom code added.' }} Ask a super admin to change it.</p>
    @endrole
</section>

<section class="egh-card wsc-section">
    <h2 class="wsc-section__title">Events sent automatically</h2>
    <div class="wsc-table-wrap">
        <table class="wsc-table">
            <thead><tr><th>When</th><th>Google (GA4 / Ads / GTM)</th><th>Meta Pixel</th><th>TikTok</th></tr></thead>
            <tbody>
                <tr><td>Every page view</td><td>page_view</td><td>PageView</td><td>page</td></tr>
                <tr><td>A flight, hotel or tour search</td><td>search</td><td>Search</td><td>Search</td></tr>
                <tr><td>Request a quote / work visa application</td><td>lead</td><td>Lead</td><td>Lead</td></tr>
                <tr><td>Contact form sent</td><td>contact</td><td>Contact</td><td>Contact</td></tr>
                <tr><td>Newsletter sign-up</td><td>subscribe</td><td>Subscribe</td><td>Subscribe</td></tr>
                <tr><td>Traveller details submitted</td><td>begin_checkout</td><td>InitiateCheckout</td><td>InitiateCheckout</td></tr>
                <tr><td>Booking confirmed and paid</td><td>purchase</td><td>Purchase</td><td>Purchase</td></tr>
            </tbody>
        </table>
    </div>
</section>
@endsection
