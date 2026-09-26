{{--
    Honest "not configured / unavailable" card with a quote-request fallback.
    Params: icon, badge, title, text, service (enquiry service key), tone (warn|muted)
--}}
<section class="card svc-state" role="status">
    <span class="svc__ic c-amber"><svg class="ic"><use href="#i-{{ $icon }}"/></svg></span>
    <div>
        <span class="badge badge--{{ $tone ?? 'warn' }}">{{ $badge }}</span>
        <h2>{{ $title }}</h2>
        <p>{{ $text }}</p>
        <p>You can still send us a request and our team will check the options for you.</p>
        <div class="row">
            <a href="{{ route('enquiries.create', ['service' => $service]) }}" class="btn btn--blue" data-track="lead">Request a quote <svg class="ic"><use href="#i-arrow-r"/></svg></a>
            <a href="{{ route('home') }}" class="btn btn--outline">Back to travel services</a>
        </div>
    </div>
</section>
