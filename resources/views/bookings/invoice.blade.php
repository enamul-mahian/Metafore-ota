@extends('layouts.site')

@section('title', 'Booking Invoice / Payment Record')
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

        $amountLabel = $payment?->status === \App\Models\FlightOrderPaymentAttempt::STATUS_SUCCEEDED
            ? 'Total Paid'
            : 'Recorded Amount';
    @endphp

    <main class="bookings-container">

        <section class="booking-detail-hero">
            <div>
                <span class="bookings-kicker">
                    CUSTOMER PAYMENT DOCUMENT
                </span>

                <h1>
                    Booking invoice / payment record
                </h1>

                <p>
                    A printable record based only on payment information stored
                    for internal booking #{{ $booking->id }}.
                </p>
            </div>

            <div class="booking-detail-actions">
                <a
                    href="{{ route('bookings.show', $booking) }}"
                    class="site-button site-button-secondary"
                >
                    Back to Booking
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

        <section class="booking-document">
            <div class="booking-document-heading">
                <div>
                    <span class="bookings-kicker">
                        INVOICE / PAYMENT RECORD
                    </span>

                    <h2>
                        Internal booking #{{ $booking->id }}
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
                    <dt>Booking Created</dt>
                    <dd>{{ $booking->created_at?->format('M j, Y g:i A') ?? 'Not available' }}</dd>
                </div>

                <div>
                    <dt>Order Resolved</dt>
                    <dd>{{ $booking->resolved_at?->format('M j, Y g:i A') ?? 'Not available' }}</dd>
                </div>

                <div>
                    <dt>Payment Record</dt>
                    <dd>{{ $payment ? '#'.$payment->id : 'Not available' }}</dd>
                </div>

                <div>
                    <dt>Payment Status</dt>
                    <dd>{{ $paymentStatus }}</dd>
                </div>

                <div>
                    <dt>{{ $amountLabel }}</dt>
                    <dd>
                        @if ($payment)
                            {{ $payment->currency }} {{ $payment->amount }}
                        @else
                            Not available
                        @endif
                    </dd>
                </div>

                <div>
                    <dt>Payment Created</dt>
                    <dd>{{ $payment?->created_at?->format('M j, Y g:i A') ?? 'Not available' }}</dd>
                </div>

                <div>
                    <dt>Payment Resolved</dt>
                    <dd>{{ $payment?->resolved_at?->format('M j, Y g:i A') ?? 'Not available' }}</dd>
                </div>
            </dl>

            <div class="booking-note-grid">
                <article>
                    <h3>Stored payment data</h3>

                    <p>
                        @if ($payment)
                            This record reflects the stored payment amount and
                            status shown above.
                        @else
                            No payment attempt is stored for this booking, so no
                            invoice amount is available.
                        @endif
                    </p>
                </article>

                <article>
                    <h3>Fare and tax detail</h3>

                    <p>
                        A fare, tax and fee breakdown is not stored in this
                        booking record and is therefore not shown here.
                    </p>
                </article>

                <article>
                    <h3>Airline ticket</h3>

                    <p>
                        This document is not an airline-issued e-ticket, ticket
                        receipt or proof of travel.
                    </p>
                </article>
            </div>
        </section>

    </main>

@endsection
