@extends('layouts.site')

@section('title', 'Booking Details')
@section('body_class', 'bookings-body')

@section('content')

    @php
        $payment = $booking->paymentAttempt;

        $orderStatus = match ($booking->status) {
            \App\Models\FlightOrderAttempt::STATUS_CREATED => 'Order Created',
            \App\Models\FlightOrderAttempt::STATUS_FAILED => 'Order Failed',
            default => 'Order Processing',
        };

        $paymentStatus = match ($payment?->status) {
            \App\Models\FlightOrderPaymentAttempt::STATUS_SUCCEEDED => 'Payment Succeeded',
            \App\Models\FlightOrderPaymentAttempt::STATUS_FAILED => 'Payment Failed',
            \App\Models\FlightOrderPaymentAttempt::STATUS_PROCESSING => 'Payment Processing',
            default => 'Payment Not Started',
        };
    @endphp

    <main class="bookings-container">

        <section class="booking-detail-hero">
            <div>
                <span class="bookings-kicker">
                    BOOKING DETAILS
                </span>

                <h1>
                    Booking #{{ $booking->id }}
                </h1>

                <p>
                    Customer booking confirmation and payment summary based on
                    records stored for your Eagle Global Hub LTD account.
                </p>
            </div>

            <div class="booking-detail-actions">
                <a
                    href="{{ route('bookings.index') }}"
                    class="site-button site-button-secondary"
                >
                    Back to Bookings
                </a>

                <a
                    href="{{ route('bookings.invoice', $booking) }}"
                    class="site-button site-button-secondary"
                >
                    Invoice / Payment Record
                </a>

                <button
                    type="button"
                    class="site-button site-button-primary"
                    onclick="window.print()"
                >
                    Print
                </button>
            </div>
        </section>

        <section class="booking-detail-grid">
            <article class="booking-detail-panel">
                <div class="booking-panel-heading">
                    <span class="bookings-kicker">
                        STATUS
                    </span>

                    <h2>
                        Booking status
                    </h2>
                </div>

                <dl class="booking-detail-list">
                    <div>
                        <dt>Order Status</dt>
                        <dd>
                            <span
                                @class([
                                    'booking-status',
                                    'booking-status-success' => $booking->status === \App\Models\FlightOrderAttempt::STATUS_CREATED,
                                    'booking-status-danger' => $booking->status === \App\Models\FlightOrderAttempt::STATUS_FAILED,
                                ])
                            >
                                {{ $orderStatus }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt>Payment Status</dt>
                        <dd>
                            <span
                                @class([
                                    'booking-status',
                                    'booking-status-success' => $payment?->status === \App\Models\FlightOrderPaymentAttempt::STATUS_SUCCEEDED,
                                    'booking-status-danger' => $payment?->status === \App\Models\FlightOrderPaymentAttempt::STATUS_FAILED,
                                ])
                            >
                                {{ $paymentStatus }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt>Booking Created</dt>
                        <dd>
                            {{ $booking->created_at?->format('M j, Y g:i A') ?? 'Not available' }}
                        </dd>
                    </div>

                    <div>
                        <dt>Order Resolved</dt>
                        <dd>
                            {{ $booking->resolved_at?->format('M j, Y g:i A') ?? 'Not available' }}
                        </dd>
                    </div>
                </dl>
            </article>

            <article class="booking-detail-panel">
                <div class="booking-panel-heading">
                    <span class="bookings-kicker">
                        PAYMENT
                    </span>

                    <h2>
                        Receipt summary
                    </h2>
                </div>

                @if ($payment)
                    <dl class="booking-detail-list">
                        <div>
                            <dt>Amount</dt>
                            <dd>{{ $payment->currency }} {{ $payment->amount }}</dd>
                        </div>

                        <div>
                            <dt>Payment Status</dt>
                            <dd>{{ $paymentStatus }}</dd>
                        </div>

                        <div>
                            <dt>Payment Created</dt>
                            <dd>
                                {{ $payment->created_at?->format('M j, Y g:i A') ?? 'Not available' }}
                            </dd>
                        </div>

                        <div>
                            <dt>Payment Resolved</dt>
                            <dd>
                                {{ $payment->resolved_at?->format('M j, Y g:i A') ?? 'Not available' }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <div class="booking-muted-state">
                        No payment attempt has been stored for this booking.
                    </div>
                @endif
            </article>
        </section>

        <section class="booking-document">
            <div class="booking-document-heading">
                <div>
                    <span class="bookings-kicker">
                        CUSTOMER DOCUMENT
                    </span>

                    <h2>
                        Booking confirmation
                    </h2>
                </div>

                <strong>
                    Eagle Global Hub LTD
                </strong>
            </div>

            <dl class="booking-document-grid">
                <div>
                    <dt>Customer</dt>
                    <dd>{{ auth()->user()->name }}</dd>
                </div>

                <div>
                    <dt>Email</dt>
                    <dd>{{ auth()->user()->email }}</dd>
                </div>

                <div>
                    <dt>Internal Booking</dt>
                    <dd>#{{ $booking->id }}</dd>
                </div>

                <div>
                    <dt>Order Status</dt>
                    <dd>{{ $orderStatus }}</dd>
                </div>

                <div>
                    <dt>Payment Status</dt>
                    <dd>{{ $paymentStatus }}</dd>
                </div>

                <div>
                    <dt>Total Paid</dt>
                    <dd>
                        @if ($payment)
                            {{ $payment->currency }} {{ $payment->amount }}
                        @else
                            Not available
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="booking-note-grid">
                <article>
                    <h3>Itinerary</h3>

                    <p>
                        Detailed route, schedule and carrier itinerary are not
                        stored in this booking record.
                    </p>
                </article>

                <article>
                    <h3>Traveler Information</h3>

                    <p>
                        Traveler details are validated during booking and are
                        not displayed from this stored summary.
                    </p>
                </article>

                <article>
                    <h3>Airline Ticket</h3>

                    <p>
                        This is a customer booking confirmation, not an
                        airline-issued e-ticket or ticket document.
                    </p>
                </article>
            </div>
        </section>

    </main>

@endsection
