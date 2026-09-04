@extends('layouts.admin')

@section('title', 'Booking #'.$booking->id)

@section('content')
<style>
.abd-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}.abd-muted{color:#64748b}.abd-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.abd-panel{border:1px solid #e5e7eb;border-radius:10px;padding:18px}.abd-panel h2{margin:0 0 14px;font-size:18px}.abd-list{display:grid;grid-template-columns:minmax(120px,1fr) 2fr;gap:10px;margin:0}.abd-list dt{color:#64748b}.abd-list dd{margin:0;word-break:break-word}.abd-back{color:#244fc7;text-decoration:none;font-weight:600}@media(max-width:720px){.abd-grid{grid-template-columns:1fr}.abd-head{display:block}.abd-back{display:inline-block;margin-top:12px}}
</style>

<div class="egh-card">
    <div class="abd-head">
        <div>
            <h1 style="margin:0">Booking #{{ $booking->id }}</h1>
            <p class="abd-muted" style="margin:6px 0 0">Read-only operational booking details.</p>
        </div>
        <a class="abd-back" href="{{ route('admin.bookings.index') }}">Back to Bookings</a>
    </div>

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
