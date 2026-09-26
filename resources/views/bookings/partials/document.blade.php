{{--
    Printable customer document (booking confirmation / payment record).
    @param string $kicker
    @param string $heading
    @param array<string, string> $rows   label => value (stored data only)
    @param array<string, string> $notes  title => explanation
--}}
<section class="card booking-document" aria-labelledby="booking-document-title">
    <div class="booking-document__head">
        <div>
            <p class="account-hello__kicker">{{ $kicker }}</p>
            <h2 class="h2" id="booking-document-title">{{ $heading }}</h2>
        </div>
        <div class="booking-document__brand">
            <img src="{{ $site->imageUrl('logo') }}" alt="" width="60" height="32">
            <strong>Eagle Global Hub LTD</strong>
        </div>
    </div>

    <dl class="booking-document__grid">
        @foreach ($rows as $label => $value)
            <div>
                <dt>{{ $label }}</dt>
                <dd>{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="booking-document__notes">
        @foreach ($notes as $title => $text)
            <article>
                <h3>{{ $title }}</h3>
                <p>{{ $text }}</p>
            </article>
        @endforeach
    </div>
</section>
