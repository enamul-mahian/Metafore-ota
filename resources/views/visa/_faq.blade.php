@php
    $visaFaqs = [
        ['Can Eagle Global Hub guarantee visa approval?', 'No. Visa decisions are made by the embassy or immigration authority of the destination country, and entry is decided at the border. We help you prepare, but approval and entry are never guaranteed.'],
        ['What do I need to check visa requirements?', 'Your passport nationality, the country you travel from, your destination country and your departure and arrival dates and times.'],
        ['Can visa requirements change?', 'Yes. Rules can change at short notice. Always confirm the latest requirements with the embassy or official government source before you travel.'],
        ['Does a requirement check submit a visa application?', 'No. A requirement check only shows information for your trip. Nothing is submitted to any embassy and no fee is charged.'],
        ['Can you help me with my visa application?', 'Yes. Send us a visa assistance request with your passport nationality and destination, and our team will explain the documents and next steps.'],
    ];
@endphp
@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Visa', 'item' => route('visa.index')],
            ],
        ],
        [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq[1]],
            ], $visaFaqs),
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
@endpush
<section class="section" id="faq">
    <div class="section__head"><div><h2>Visa FAQs</h2><p>What a requirement check can and cannot tell you.</p></div></div>
    <div class="faq">
        @foreach ($visaFaqs as $index => [$question, $answer])
            <details @if ($index === 0) open @endif><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
        @endforeach
    </div>
</section>
