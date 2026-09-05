@extends('layouts.admin')

@section('title', 'Bookings')

@section('content')

<x-admin.page-header title="Bookings" description="Read-only operational view of persisted flight order attempts." icon="B" eyebrow="Travel operations">
    <span class="admin-status-badge">{{ $bookings->total() }} total</span>
</x-admin.page-header>

<div class="egh-card">

    <div class="ab-table-wrap">
        <table class="ab-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Provider</th>
                <th>Order status</th>
                <th>Payment</th>
                <th>Created</th>
                <th>Resolved</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td>#{{ $booking->id }}</td>
                    <td>
                        <strong>{{ $booking->user->name }}</strong><br>
                        <span class="ab-muted">{{ $booking->user->email }}</span>
                    </td>
                    <td>{{ ucfirst($booking->provider) }}</td>
                    <td><span class="ab-status">{{ ucfirst($booking->status) }}</span></td>
                    <td>
                        @if($booking->paymentAttempt)
                            <span class="ab-status">{{ ucfirst($booking->paymentAttempt->status) }}</span><br>
                            {{ $booking->paymentAttempt->currency }} {{ $booking->paymentAttempt->amount }}
                        @else
                            <span class="ab-muted">Not started</span>
                        @endif
                    </td>
                    <td>{{ $booking->created_at?->format('M j, Y g:i A') ?? '—' }}</td>
                    <td>{{ $booking->resolved_at?->format('M j, Y g:i A') ?? '—' }}</td>
                    <td><a class="ab-link" href="{{ route('admin.bookings.show', $booking) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="8">No persisted flight bookings found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($bookings->hasPages())
        <div class="ab-pagination">{{ $bookings->links() }}</div>
    @endif
</div>
@endsection
