@extends('layouts.admin')

@section('title', 'Booking #'.$booking->id)

@section('content')

<x-admin.page-header :title="'Booking #'.$booking->id" description="Read-only operational booking details." icon="B" eyebrow="Travel operations">
    <a class="egh-button secondary" href="{{ route('admin.bookings.index') }}">Back to Bookings</a>
</x-admin.page-header>

<div class="egh-card">

    <div class="abd-grid">
        <section class="abd-panel">
            <h2>Order attempt</h2>
            <dl class="abd-list">
                <dt>Internal ID</dt><dd>#{{ $booking->id }}</dd>
                <dt>Customer</dt><dd>{{ $booking->user->name }}</dd>
                <dt>Email</dt><dd>{{ $booking->user->email }}</dd>
                <dt>Provider</dt><dd>{{ ucfirst($booking->provider) }}</dd>
                <dt>Status</dt><dd>{{ ucfirst($booking->status) }}</dd>
                <dt>Created</dt><dd>{{ $booking->created_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                <dt>Resolved</dt><dd>{{ $booking->resolved_at?->format('M j, Y g:i A') ?? 'Not resolved' }}</dd>
            </dl>
        </section>

        <section class="abd-panel">
            <h2>Payment attempt</h2>
            @if($booking->paymentAttempt)
                <dl class="abd-list">
                    <dt>Status</dt><dd>{{ ucfirst($booking->paymentAttempt->status) }}</dd>
                    <dt>Amount</dt><dd>{{ $booking->paymentAttempt->currency }} {{ $booking->paymentAttempt->amount }}</dd>
                    <dt>Resolved</dt><dd>{{ $booking->paymentAttempt->resolved_at?->format('M j, Y g:i A') ?? 'Not resolved' }}</dd>
                </dl>
            @else
                <p class="abd-muted">No payment attempt is associated with this booking.</p>
            @endif
        </section>
    </div>
</div>
@endsection
