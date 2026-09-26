@extends('layouts.site')

@section('title', 'Booking Invoice / Payment Record')
@section('seo_title', 'Booking Invoice | Eagle Global Hub')
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

    <section class="page account-page">
        <div class="container">
            <nav class="crumbs no-print" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span><a href="{{ route('bookings.index') }}">My Bookings</a></span>
                <span><a href="{{ route('bookings.show', $booking) }}">Booking #{{ $booking->id }}</a></span>
                <span>Invoice</span>
            </nav>

            <div class="account-layout">
                @include('account.partials.side-menu', ['active' => 'bookings'])

                <div class="stack">
                    <div class="card booking-hero no-print">
                        <div>
                            <p class="account-hello__kicker">Customer payment document</p>
                            <h1 class="h1">Booking invoice / payment record</h1>
                            <p class="muted small">
                                A printable record based only on payment information stored for internal booking #{{ $booking->id }}.
                            </p>
                        </div>

                        <div class="booking-actions">
                            <a href="{{ route('bookings.show', $booking) }}" class="btn btn--outline btn--sm">
                                <svg class="ic"><use href="#i-chev-l"/></svg>Back to Booking
                            </a>
                            <button type="button" class="btn btn--blue btn--sm" onclick="window.print()">
                                <svg class="ic"><use href="#i-download"/></svg>Print
                            </button>
                        </div>
                    </div>

                    @include('bookings.partials.document', [
                        'kicker' => 'Invoice / payment record',
                        'heading' => 'Internal booking #'.$booking->id,
                        'rows' => [
                            'Customer' => auth()->user()->name,
                            'Email' => auth()->user()->email,
                            'Internal Booking' => '#'.$booking->id,
                            'Order Status' => $orderStatus,
                            'Booking Created' => $booking->created_at?->format('M j, Y g:i A') ?? 'Not available',
                            'Order Resolved' => $booking->resolved_at?->format('M j, Y g:i A') ?? 'Not available',
                            'Payment Record' => $payment ? '#'.$payment->id : 'Not available',
                            'Payment Status' => $paymentStatus,
                            $amountLabel => $payment ? $payment->currency.' '.$payment->amount : 'Not available',
                            'Payment Created' => $payment?->created_at?->format('M j, Y g:i A') ?? 'Not available',
                            'Payment Resolved' => $payment?->resolved_at?->format('M j, Y g:i A') ?? 'Not available',
                        ],
                        'notes' => [
                            'Stored payment data' => $payment
                                ? 'This record reflects the stored payment amount and status shown above.'
                                : 'No payment attempt is stored for this booking, so no invoice amount is available.',
                            'Fare and tax detail' => 'A fare, tax and fee breakdown is not stored in this booking record and is therefore not shown here.',
                            'Airline ticket' => 'This document is not an airline-issued e-ticket, ticket receipt or proof of travel.',
                        ],
                    ])
                </div>
            </div>
        </div>
    </section>

@endsection
