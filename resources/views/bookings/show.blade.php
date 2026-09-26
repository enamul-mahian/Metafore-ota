@extends('layouts.site')

@section('title', 'Booking Details')
@section('seo_title', 'Booking Details | Eagle Global Hub')
@section('robots', 'noindex,nofollow')
@section('body_class', 'bookings-body account-body')

@section('content')

    @php
        $payment = $booking->paymentAttempt;

        $orderStatus = match ($booking->status) {
            \App\Models\FlightOrderAttempt::STATUS_CREATED => 'Order Created',
            \App\Models\FlightOrderAttempt::STATUS_FAILED => 'Order Failed',
            default => 'Order Processing',
        };

        $orderTone = match ($booking->status) {
            \App\Models\FlightOrderAttempt::STATUS_CREATED => 'success',
            \App\Models\FlightOrderAttempt::STATUS_FAILED => 'danger',
            default => 'warn',
        };

        $paymentStatus = match ($payment?->status) {
            \App\Models\FlightOrderPaymentAttempt::STATUS_SUCCEEDED => 'Payment Succeeded',
            \App\Models\FlightOrderPaymentAttempt::STATUS_FAILED => 'Payment Failed',
            \App\Models\FlightOrderPaymentAttempt::STATUS_PROCESSING => 'Payment Processing',
            default => 'Payment Not Started',
        };

        $paymentTone = match ($payment?->status) {
            \App\Models\FlightOrderPaymentAttempt::STATUS_SUCCEEDED => 'success',
            \App\Models\FlightOrderPaymentAttempt::STATUS_FAILED => 'danger',
            \App\Models\FlightOrderPaymentAttempt::STATUS_PROCESSING => 'warn',
            default => 'muted',
        };
    @endphp

    <section class="page account-page">
        <div class="container">
            <nav class="crumbs no-print" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span><a href="{{ route('bookings.index') }}">My Bookings</a></span>
                <span>Booking #{{ $booking->id }}</span>
            </nav>

            <div class="account-layout">
                @include('account.partials.side-menu', ['active' => 'bookings'])

                <div class="stack">
                    <div class="card booking-hero">
                        <div>
                            <p class="account-hello__kicker">Booking details</p>
                            <h1 class="h1">Booking #{{ $booking->id }}</h1>
                            <p class="muted small">
                                Customer booking confirmation and payment summary based on records stored for your Eagle Global Hub LTD account.
                            </p>
                            <div class="row booking-hero__badges">
                                <span class="badge badge--{{ $orderTone }}">{{ $orderStatus }}</span>
                                <span class="badge badge--{{ $paymentTone }}">{{ $paymentStatus }}</span>
                            </div>
                        </div>

                        <div class="booking-actions no-print">
                            <a href="{{ route('bookings.index') }}" class="btn btn--outline btn--sm">
                                <svg class="ic"><use href="#i-chev-l"/></svg>Back to Bookings
                            </a>
                            <a href="{{ route('bookings.invoice', $booking) }}" class="btn btn--outline btn--sm">
                                <svg class="ic"><use href="#i-file"/></svg>Invoice / Payment Record
                            </a>
                            <button type="button" class="btn btn--blue btn--sm" onclick="window.print()">
                                <svg class="ic"><use href="#i-download"/></svg>Print
                            </button>
                        </div>
                    </div>

                    <div class="grid-2 no-print">
                        <article class="card">
                            <h2 class="card__title">Booking status</h2>
                            <dl class="kv">
                                <div><dt>Order Status</dt><dd><span class="badge badge--{{ $orderTone }}">{{ $orderStatus }}</span></dd></div>
                                <div><dt>Payment Status</dt><dd><span class="badge badge--{{ $paymentTone }}">{{ $paymentStatus }}</span></dd></div>
                                <div><dt>Booking Created</dt><dd>{{ $booking->created_at?->format('M j, Y g:i A') ?? 'Not available' }}</dd></div>
                                <div><dt>Order Resolved</dt><dd>{{ $booking->resolved_at?->format('M j, Y g:i A') ?? 'Not available' }}</dd></div>
                            </dl>
                        </article>

                        <article class="card">
                            <h2 class="card__title">Receipt summary</h2>
                            @if ($payment)
                                <dl class="kv">
                                    <div><dt>Amount</dt><dd><b>{{ $payment->currency }} {{ $payment->amount }}</b></dd></div>
                                    <div><dt>Payment Status</dt><dd>{{ $paymentStatus }}</dd></div>
                                    <div><dt>Payment Created</dt><dd>{{ $payment->created_at?->format('M j, Y g:i A') ?? 'Not available' }}</dd></div>
                                    <div><dt>Payment Resolved</dt><dd>{{ $payment->resolved_at?->format('M j, Y g:i A') ?? 'Not available' }}</dd></div>
                                </dl>
                            @else
                                <p class="muted small">No payment attempt has been stored for this booking.</p>
                            @endif
                        </article>
                    </div>

                    @include('bookings.partials.document', [
                        'kicker' => 'Customer document',
                        'heading' => 'Booking confirmation',
                        'rows' => [
                            'Customer' => auth()->user()->name,
                            'Email' => auth()->user()->email,
                            'Internal Booking' => '#'.$booking->id,
                            'Order Status' => $orderStatus,
                            'Payment Status' => $paymentStatus,
                            'Total Paid' => $payment ? $payment->currency.' '.$payment->amount : 'Not available',
                        ],
                        'notes' => [
                            'Itinerary' => 'Detailed route, schedule and carrier itinerary are not stored in this booking record.',
                            'Traveler Information' => 'Traveler details are validated during booking and are not displayed from this stored summary.',
                            'Airline Ticket' => 'This is a customer booking confirmation, not an airline-issued e-ticket or ticket document.',
                        ],
                    ])
                </div>
            </div>
        </div>
    </section>

@endsection
