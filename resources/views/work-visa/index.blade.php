@extends('layouts.site')

@section('title', 'Work Visa Processing')
@section('body_class', 'wv-body')
@section('og_image', $site->imageUrl('page.work_visa'))

@php
    $enquiryUrl = Route::has('enquiries.create') ? route('enquiries.create', 'visa') : null;
    $applyUrl = route('work-visa.apply');

    $faqs = [
        [
            'q' => 'Do I need a job offer before applying for a work visa?',
            'a' => 'Most countries only issue a work visa with a job offer or employer sponsorship. Tell us your destination and job category in the application and our team will explain what applies to your case.',
        ],
        [
            'q' => 'How long does work visa processing take?',
            'a' => 'It depends on the country, the employer and the embassy. The estimated processing time for each destination is shown on this page. Embassy timelines can change, and every status update appears in your account.',
        ],
        [
            'q' => 'Which documents do I need to apply online?',
            'a' => 'You need a valid passport to apply. A passport-size photo, CV and education or experience certificates help us assess your profile, and some destinations list extra requirements.',
        ],
        [
            'q' => 'How much does work visa processing cost?',
            'a' => 'Where our service fee is set for a destination it is shown on this page. Embassy fees, medical tests and other third-party costs are separate, so ask our team for a full quotation before you pay anything.',
        ],
        [
            'q' => 'Can I track my work visa application?',
            'a' => 'Yes. After you submit, you can follow every status change and upload missing documents from the Work Visa section of your account.',
        ],
        [
            'q' => 'Who can see the documents I upload?',
            'a' => 'Documents are stored privately. Only you and authorised Eagle Global Hub staff can open them. They are never published on the website.',
        ],
    ];

    $steps = [
        ['Choose a destination', 'Pick the country and the job category that match your skills and experience.'],
        ['Apply online', 'Fill in your personal, passport and contact details in one secure form.'],
        ['Upload documents', 'Add your passport bio page, photo, CV and certificates as PDF, JPG or PNG.'],
        ['Document review', 'Our team checks your file and tells you if anything is missing.'],
        ['Visa processing', 'We work through the employer and visa application steps for your destination.'],
        ['Decision', 'You see the outcome and every update in your account as soon as it is recorded.'],
    ];

    $documents = [
        ['Passport bio page', 'Required to submit an application.'],
        ['Passport-size photo', 'Recent, plain background.'],
        ['CV / resume', 'Work history and skills.'],
        ['Certificates', 'Education, training and experience letters.'],
    ];

    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Work Visa Processing', 'item' => route('work-visa.index')],
                ],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn (array $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
                ], $faqs),
            ],
            [
                '@type' => 'Service',
                'name' => 'Work Visa Processing',
                'serviceType' => 'Work visa and work permit processing',
                'provider' => ['@type' => 'TravelAgency', 'name' => 'Eagle Global Hub Ltd.', 'url' => route('home')],
                'areaServed' => 'BD',
            ],
        ],
    ];
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <section class="split-hero wv-hero">
        <div class="split-hero__img" style="background-image:url('{{ $site->imageUrl('page.work_visa') }}')" role="img" aria-label="Skilled workers ready to work abroad"></div>
        <div class="container split-hero__in">
            <div class="split-hero__copy">
                <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>Work Visa Processing</span></nav>
                <h1 class="red">Work Visa Processing</h1>
                <p class="split-hero__sub">Your Global Career Starts Here</p>
                <ul class="ticks">
                    <li>Job Visa &amp; Work Permits</li>
                    <li>Skilled Worker Visa</li>
                    <li>Employer Sponsored Visa</li>
                    <li>Document Assistance</li>
                </ul>
                <div class="row">
                    @if ($destinations->isNotEmpty())
                        <a href="{{ $applyUrl }}" class="btn btn--blue btn--lg" data-track="begin_checkout">Apply Now <svg class="ic"><use href="#i-arrow-r"/></svg></a>
                    @elseif ($enquiryUrl)
                        <a href="{{ $enquiryUrl }}" class="btn btn--blue btn--lg" data-track="contact">Ask About Work Visas <svg class="ic"><use href="#i-arrow-r"/></svg></a>
                    @endif
                    <a href="#process" class="btn btn--outline btn--lg">How It Works</a>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <section class="section" id="destinations" aria-labelledby="wv-destinations-title">
            <div class="section__head">
                <div>
                    <h2 id="wv-destinations-title">Popular Work Visa Destinations</h2>
                    <p>Countries we currently process work visa applications for.</p>
                </div>
                @if ($destinations->isNotEmpty())
                    <a href="{{ $applyUrl }}" class="pill">Start Application <svg class="ic"><use href="#i-arrow-r"/></svg></a>
                @endif
            </div>

            @if ($destinations->isEmpty())
                <div class="card empty wv-empty">
                    <svg class="ic"><use href="#i-briefcase"/></svg>
                    <p class="h3">Online applications are not open yet</p>
                    <p>We are preparing the destinations we can process work visas for. Tell us where you want to work and our team will get back to you.</p>
                    @if ($enquiryUrl)
                        <a href="{{ $enquiryUrl }}" class="btn btn--blue" data-track="contact">Send a Work Visa Enquiry <svg class="ic"><use href="#i-arrow-r"/></svg></a>
                    @endif
                </div>
            @else
                <div class="tiles tiles--6 wv-tiles">
                    @foreach ($destinations as $destination)
                        <a href="{{ route('work-visa.apply', ['destination' => $destination->slug]) }}" class="tile">
                            <span class="flag-lg" aria-hidden="true">{{ $destination->flag() }}</span>
                            {{ $destination->name() }}
                            <small>{{ collect([$destination->visa_type, $destination->processing_time])->filter()->implode(' · ') }}</small>
                            @if ($destination->formattedFee())
                                <small class="wv-tile-fee">From {{ $destination->formattedFee() }}</small>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        @if ($jobCategories->isNotEmpty())
            <section class="section" aria-labelledby="wv-jobs-title">
                <div class="section__head"><div><h2 id="wv-jobs-title">Jobs in Demand</h2><p>Job categories open for applications across our destinations.</p></div></div>
                <ul class="chips">
                    @foreach ($jobCategories as $category)
                        <li class="chip"><svg class="ic"><use href="#i-briefcase"/></svg>{{ $category }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="section" id="process" aria-labelledby="wv-process-title">
            <div class="section__head"><div><h2 id="wv-process-title">How Work Visa Processing Works</h2><p>A clear, step-by-step path you can follow from your account.</p></div></div>
            <ol class="process">
                @foreach ($steps as [$stepTitle, $stepText])
                    <li><b>{{ $stepTitle }}</b><p>{{ $stepText }}</p></li>
                @endforeach
            </ol>
        </section>

        @if ($destinations->isNotEmpty())
            <section class="section" id="fees" aria-labelledby="wv-fees-title">
                <div class="section__head"><div><h2 id="wv-fees-title">{{ $hasFees ? 'Fees & Processing Time' : 'Processing Time' }}</h2><p>Estimated timelines per destination. Embassy timelines can change.</p></div></div>
                <div class="table-wrap">
                    <table class="table wv-table">
                        <thead>
                            <tr>
                                <th scope="col">Country</th>
                                <th scope="col">Visa category</th>
                                <th scope="col">Processing time</th>
                                @if ($hasFees)<th scope="col" class="num">Service fee from</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($destinations as $destination)
                                <tr>
                                    <td><span aria-hidden="true">{{ $destination->flag() }}</span> {{ $destination->name() }}</td>
                                    <td>{{ $destination->visa_type ?: 'Work visa' }}</td>
                                    <td>{{ $destination->processing_time ?: 'Ask our team' }}</td>
                                    @if ($hasFees)<td class="num">{{ $destination->formattedFee() ?? 'On request' }}</td>@endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($hasFees)
                    <p class="fee-note">Service fees cover our processing work. Embassy fees, medical tests, clearance fees and air tickets are separate.</p>
                @endif
            </section>
        @endif

        <section class="section" id="documents" aria-labelledby="wv-docs-title">
            <div class="section__head"><div><h2 id="wv-docs-title">Required Documents</h2><p>Keep these ready as PDF, JPG or PNG files (up to 5 MB each).</p></div></div>
            @php($withRequirements = $destinations->filter(fn ($destination) => $destination->requirementList() !== []))
            <div class="{{ $withRequirements->isNotEmpty() ? 'grid-2' : '' }} wv-docs">
                <div class="card">
                    <h3 class="card__title">For every application</h3>
                    <ul class="doclist">
                        @foreach ($documents as [$docTitle, $docText])
                            <li><svg class="ic"><use href="#i-check"/></svg><span><b>{{ $docTitle }}</b> · {{ $docText }}</span></li>
                        @endforeach
                    </ul>
                </div>
                @if ($withRequirements->isNotEmpty())
                    <div class="card">
                        <h3 class="card__title">Country-specific requirements</h3>
                        <div class="faq wv-req">
                            @foreach ($withRequirements as $destination)
                                <details>
                                    <summary><span><span aria-hidden="true">{{ $destination->flag() }}</span> {{ $destination->name() }}</span></summary>
                                    <ul class="doclist">
                                        @foreach ($destination->requirementList() as $requirement)
                                            <li><svg class="ic"><use href="#i-file"/></svg><span>{{ $requirement }}</span></li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <section class="band">
        <div class="container">
            <div class="section__head"><div><h2>Why Apply Online With Eagle Global Hub</h2><p>Everything about your application in one place.</p></div></div>
            <div class="grid-4 wv-feats">
                <div class="feat"><span class="svc__ic c-blue"><svg class="ic"><use href="#i-file"/></svg></span><div><b>One simple form</b><p>Personal, passport and job details with document upload.</p></div></div>
                <div class="feat"><span class="svc__ic c-green"><svg class="ic"><use href="#i-lock"/></svg></span><div><b>Private documents</b><p>Files are stored privately, never on the public website.</p></div></div>
                <div class="feat"><span class="svc__ic c-amber"><svg class="ic"><use href="#i-clock"/></svg></span><div><b>Status tracking</b><p>See every update to your application in your account.</p></div></div>
                <div class="feat"><span class="svc__ic c-red"><svg class="ic"><use href="#i-headset"/></svg></span><div><b>Human support</b><p>Our team reviews each file and contacts you about next steps.</p></div></div>
            </div>
        </div>
    </section>

    <div class="container">
        <section class="section" id="faq" aria-labelledby="wv-faq-title">
            <div class="section__head"><div><h2 id="wv-faq-title">Work Visa FAQs</h2><p>Answers to what applicants ask us most.</p></div></div>
            <div class="faq">
                @foreach ($faqs as $faq)
                    <details @if ($loop->first) open @endif><summary>{{ $faq['q'] }}</summary><p>{{ $faq['a'] }}</p></details>
                @endforeach
            </div>
        </section>

        <div class="cta-band wv-cta">
            <div>
                <h2>Ready to work abroad?</h2>
                <p>{{ $destinations->isNotEmpty() ? 'Start your application online and track it from your account.' : 'Tell us where you want to work and we will get back to you.' }}</p>
            </div>
            @if ($destinations->isNotEmpty())
                <a href="{{ $applyUrl }}" class="btn btn--white" data-track="begin_checkout">Apply Now <svg class="ic"><use href="#i-arrow-r"/></svg></a>
            @elseif ($enquiryUrl)
                <a href="{{ $enquiryUrl }}" class="btn btn--white" data-track="contact">Send an Enquiry <svg class="ic"><use href="#i-arrow-r"/></svg></a>
            @endif
        </div>
        <div class="wv-spacer" aria-hidden="true"></div>
    </div>
@endsection
