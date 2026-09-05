@extends('layouts.site')

@section('title', 'My Bookings')
@section('body_class', 'bookings-body')

@section('content')

    <main class="bookings-container">

        <section class="bookings-hero">
            <div>
                <span class="bookings-kicker">
                    TRAVEL HISTORY
                </span>

                <h1>
                    My Bookings
                </h1>

                <p>
                    Review flight booking attempts created from your account,
                    including order and payment status from stored records.
                </p>
            </div>

            @can('flights.search')
                <a
                    href="{{ route('flights.index') }}"
                    class="site-button site-button-primary"
                >
                    Search Flights
                </a>
            @endcan
        </section>

        @if ($bookings->isEmpty())
            <section class="bookings-empty">
                <span aria-hidden="true">&#9992;</span>

                <h2>
                    No flight bookings yet
                </h2>

                <p>
                    Your confirmed order attempts and payment status will
                    appear here after you complete the secure flight booking
                    flow.
                </p>

                @can('flights.search')
                    <a
                        href="{{ route('flights.index') }}"
                        class="site-button site-button-primary"
                    >
                        Start Flight Search
                    </a>
                @endcan
            </section>
        @else
            <section
                class="bookings-list"
                aria-label="Flight booking list"
            >
                @foreach ($bookings as $booking)
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

                    <article class="booking-card">
                        <div class="booking-card-main">
                            <span class="bookings-kicker">
                                Flight Booking
                            </span>

                            <h2>
                                Booking #{{ $booking->id }}
                            </h2>

                            <dl class="booking-summary-grid">
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
                                    <dt>Created</dt>
                                    <dd>
                                        {{ $booking->created_at?->format('M j, Y g:i A') ?? 'Not available' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt>Payment Amount</dt>
                                    <dd>
                                        @if ($payment)
                                            {{ $payment->currency }} {{ $payment->amount }}
                                        @else
                                            Not available
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <a
                            href="{{ route('bookings.show', $booking) }}"
                            class="booking-card-link"
                        >
                            View Details
                        </a>
                    </article>
                @endforeach
            </section>

            @if ($bookings->hasPages())
                <nav
                    class="booking-pagination"
                    aria-label="Booking list pagination"
                >
                    <p>
                        Showing {{ $bookings->firstItem() }}&ndash;{{ $bookings->lastItem() }}
                        of {{ $bookings->total() }} bookings
                    </p>

                    <div>
                        @if ($bookings->onFirstPage())
                            <span aria-disabled="true">Previous</span>
                        @else
                            <a href="{{ $bookings->previousPageUrl() }}" rel="prev">
                                Previous
                            </a>
                        @endif

                        <strong aria-current="page">
                            Page {{ $bookings->currentPage() }} of {{ $bookings->lastPage() }}
                        </strong>

                        @if ($bookings->hasMorePages())
                            <a href="{{ $bookings->nextPageUrl() }}" rel="next">
                                Next
                            </a>
                        @else
                            <span aria-disabled="true">Next</span>
                        @endif
                    </div>
                </nav>
            @endif
        @endif

    </main>

@endsection
