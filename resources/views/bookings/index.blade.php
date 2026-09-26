@extends('layouts.site')

@section('title', 'My Bookings')
@section('seo_title', 'My Bookings | Eagle Global Hub')
@section('robots', 'noindex,nofollow')
@section('body_class', 'bookings-body account-body')

@section('content')
    <section class="page account-page">
        <div class="container">
            <nav class="crumbs" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                @feature('account')
                    <span><a href="{{ route('account.overview') }}">My Account</a></span>
                @endfeature
                <span>My Bookings</span>
            </nav>

            <div class="account-layout">
                @include('account.partials.side-menu', ['active' => 'bookings'])

                <div>
                    <div class="row row--between account-head">
                        <div>
                            <h1 class="h1">My Bookings</h1>
                            <p class="muted small">
                                Flight booking attempts created from your account, with order and payment
                                status from stored records.
                            </p>
                        </div>

                        @can('flights.search')
                            <a href="{{ route('flights.index') }}" class="btn btn--blue btn--sm">
                                <svg class="ic"><use href="#i-plus"/></svg>Search Flights
                            </a>
                        @endcan
                    </div>

                    <nav class="tabs-line" aria-label="Booking types">
                        <a href="{{ route('bookings.index') }}" class="is-active" aria-current="page">
                            <svg class="ic"><use href="#i-plane"/></svg>Flights
                            <span class="count">{{ $bookings->total() }}</span>
                        </a>
                        @if (Route::has('work-visa.applications.index'))
                            <a href="{{ route('work-visa.applications.index') }}">
                                <svg class="ic"><use href="#i-file"/></svg>Work Visa
                            </a>
                        @endif
                    </nav>

                    @if ($bookings->isEmpty())
                        <div class="card empty">
                            <svg class="ic"><use href="#i-plane"/></svg>
                            <h2 class="h3">No flight bookings yet</h2>
                            <p>
                                Your confirmed order attempts and payment status will appear here after you
                                complete the secure flight booking flow.
                            </p>
                            @can('flights.search')
                                <a href="{{ route('flights.index') }}" class="btn btn--blue">
                                    Start Flight Search
                                </a>
                            @endcan
                        </div>
                    @else
                        <section aria-label="Flight booking list">
                            @foreach ($bookings as $booking)
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

                                <article class="booking booking-row">
                                    <span class="booking__ic c-blue" aria-hidden="true"><svg class="ic"><use href="#i-plane"/></svg></span>

                                    <div>
                                        <b>Booking #{{ $booking->id }}</b>
                                        <small>Flight booking</small>
                                    </div>

                                    <div>
                                        <small>Created</small>
                                        <b>{{ $booking->created_at?->format('M j, Y g:i A') ?? 'Not available' }}</b>
                                    </div>

                                    <div class="booking-row__status">
                                        <span class="badge badge--{{ $orderTone }}">{{ $orderStatus }}</span>
                                        <span class="badge badge--{{ $paymentTone }}">{{ $paymentStatus }}</span>
                                    </div>

                                    <div>
                                        <small>Payment Amount</small>
                                        <b>
                                            @if ($payment)
                                                {{ $payment->currency }} {{ $payment->amount }}
                                            @else
                                                Not available
                                            @endif
                                        </b>
                                    </div>

                                    <a href="{{ route('bookings.show', $booking) }}" class="btn btn--blue btn--sm">
                                        View Details
                                    </a>
                                </article>
                            @endforeach
                        </section>

                        @if ($bookings->hasPages())
                            <nav class="account-pager" aria-label="Booking list pagination">
                                <p>
                                    Showing {{ $bookings->firstItem() }}&ndash;{{ $bookings->lastItem() }}
                                    of {{ $bookings->total() }} bookings
                                </p>

                                <div>
                                    @if ($bookings->onFirstPage())
                                        <span class="btn btn--outline btn--sm is-disabled" aria-disabled="true">Previous</span>
                                    @else
                                        <a href="{{ $bookings->previousPageUrl() }}" rel="prev" class="btn btn--outline btn--sm">
                                            Previous
                                        </a>
                                    @endif

                                    <strong aria-current="page">
                                        Page {{ $bookings->currentPage() }} of {{ $bookings->lastPage() }}
                                    </strong>

                                    @if ($bookings->hasMorePages())
                                        <a href="{{ $bookings->nextPageUrl() }}" rel="next" class="btn btn--outline btn--sm">
                                            Next
                                        </a>
                                    @else
                                        <span class="btn btn--outline btn--sm is-disabled" aria-disabled="true">Next</span>
                                    @endif
                                </div>
                            </nav>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
