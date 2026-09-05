@extends('layouts.admin')

@section('title', 'Reports')

@section('content')

<x-admin.page-header title="Reports" description="Read-only reporting from persisted application records." icon="R" eyebrow="Operational reporting">
    <span class="admin-muted">
        @if($filters['from'] || $filters['to'])
            Booking and payment activity:
            {{ $filters['from'] ? \Illuminate\Support\Carbon::parse($filters['from'])->format('M j, Y') : 'earliest' }}
            to
            {{ $filters['to'] ? \Illuminate\Support\Carbon::parse($filters['to'])->format('M j, Y') : 'present' }}
        @else
            Booking and payment activity: all time
        @endif
    </span>
</x-admin.page-header>

<form method="GET" action="{{ route('admin.reports.index') }}" class="egh-card rpt-filter">
    <label class="rpt-field">
        <span>From</span>
        <input type="date" name="from" value="{{ $filters['from'] }}">
        @error('from')<small class="rpt-error">{{ $message }}</small>@enderror
    </label>
    <label class="rpt-field">
        <span>To</span>
        <input type="date" name="to" value="{{ $filters['to'] }}">
        @error('to')<small class="rpt-error">{{ $message }}</small>@enderror
    </label>
    <button type="submit" class="egh-button">Apply date range</button>
    @if($filters['from'] || $filters['to'])
        <a href="{{ route('admin.reports.index') }}" class="egh-button secondary">Clear</a>
    @endif
</form>

<div class="rpt-grid">
    <section class="egh-card">
        <div class="rpt-section-head">
            <div>
                <h2>Bookings</h2>
                <span class="rpt-muted">Persisted flight order attempts</span>
            </div>
        </div>
        <div class="rpt-summary">
            <div class="rpt-stat"><strong>{{ $bookingTotal }}</strong><span>Total</span></div>
            @foreach($bookingStatusCounts as $status => $count)
                <div class="rpt-stat"><strong>{{ $count }}</strong><span>{{ $status }}</span></div>
            @endforeach
        </div>
    </section>

    <section class="egh-card">
        <div class="rpt-section-head">
            <div>
                <h2>Payments</h2>
                <span class="rpt-muted">Persisted payment attempts</span>
            </div>
        </div>
        <div class="rpt-summary">
            <div class="rpt-stat"><strong>{{ $paymentTotal }}</strong><span>Total</span></div>
            @foreach($paymentStatusCounts as $status => $count)
                <div class="rpt-stat"><strong>{{ $count }}</strong><span>{{ $status }}</span></div>
            @endforeach
        </div>
    </section>
</div>

<section class="egh-card rpt-section">
    <div class="rpt-section-head">
        <div>
            <h2>Successful payment volume</h2>
            <span class="rpt-muted">Succeeded persisted payments, kept separate by currency</span>
        </div>
    </div>
    <div class="rpt-table-wrap">
        <table class="rpt-table">
            <thead><tr><th>Currency</th><th>Successful payment volume</th></tr></thead>
            <tbody>
            @forelse($successfulPaymentVolumes as $volume)
                <tr><td><strong>{{ $volume['currency'] }}</strong></td><td>{{ $volume['currency'] }} {{ $volume['amount'] }}</td></tr>
            @empty
                <tr><td colspan="2">No successful persisted payments in this date range.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="rpt-section">
    <div class="rpt-section-head">
        <div>
            <h2>Business profiles</h2>
            <span class="rpt-muted">All-time persisted records; date filters do not apply</span>
        </div>
    </div>
    <div class="rpt-profile-grid">
        @foreach($profileSummaries as $label => $summary)
            <article class="egh-card rpt-profile">
                <h3>{{ $label }}</h3>
                <div class="rpt-profile-total">{{ $summary['total'] }} <span class="rpt-total-label">total</span></div>
                <dl>
                    @foreach($summary['statuses'] as $status => $count)
                        <div><dt>{{ $status }}</dt><dd>{{ $count }}</dd></div>
                    @endforeach
                </dl>
            </article>
        @endforeach
    </div>
</section>

<div class="rpt-grid">
    <section class="egh-card">
        <div class="rpt-section-head">
            <div>
                <h2>Recent bookings</h2>
                <span class="rpt-muted">Latest persisted records in the selected date range</span>
            </div>
        </div>
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead><tr><th>ID</th><th>Customer</th><th>Order</th><th>Payment</th><th>Created</th></tr></thead>
                <tbody>
                @forelse($recentBookings as $booking)
                    <tr>
                        <td>#{{ $booking->id }}</td>
                        <td><strong>{{ $booking->user->name }}</strong><br><span class="rpt-muted">{{ $booking->user->email }}</span></td>
                        <td><span class="rpt-status">{{ $booking->status }}</span></td>
                        <td>
                            @if($booking->paymentAttempt)
                                <span class="rpt-status">{{ $booking->paymentAttempt->status }}</span>
                            @else
                                <span class="rpt-muted">Not started</span>
                            @endif
                        </td>
                        <td>{{ $booking->created_at?->format('M j, Y g:i A') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No persisted flight bookings in this date range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="egh-card">
        <div class="rpt-section-head">
            <div>
                <h2>Recent successful payments</h2>
                <span class="rpt-muted">Latest succeeded persisted records in the selected date range</span>
            </div>
        </div>
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead><tr><th>ID</th><th>Amount</th><th>Resolved / created</th></tr></thead>
                <tbody>
                @forelse($recentSuccessfulPayments as $payment)
                    <tr>
                        <td>#{{ $payment->id }}</td>
                        <td>{{ $payment->currency }} {{ $payment->amount }}</td>
                        <td>{{ ($payment->resolved_at ?? $payment->created_at)?->format('M j, Y g:i A') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No successful persisted payments in this date range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
