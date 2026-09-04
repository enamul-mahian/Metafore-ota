@extends('layouts.admin')

@section('title', 'Bookings')

@section('content')
<style>
.ab-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}.ab-muted{color:#64748b}.ab-table-wrap{overflow:auto}.ab-table{width:100%;border-collapse:collapse;font-size:14px}.ab-table th,.ab-table td{padding:11px 8px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}.ab-table th{font-size:12px;text-transform:uppercase;color:#64748b;white-space:nowrap}.ab-status{display:inline-block;border-radius:999px;padding:4px 8px;background:#eef2ff;color:#334155;white-space:nowrap}.ab-link{color:#244fc7;text-decoration:none;font-weight:600}.ab-pagination{margin-top:18px}
</style>

<div class="egh-card">
    <div class="ab-head">
        <div>
            <h1 style="margin:0">Bookings</h1>
            <p class="ab-muted" style="margin:6px 0 0">Read-only operational view of persisted flight order attempts.</p>
        </div>
        <span class="ab-muted">{{ $bookings->total() }} total</span>
    </div>

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
