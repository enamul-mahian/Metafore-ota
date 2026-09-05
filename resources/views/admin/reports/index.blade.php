@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
<style>
.rpt-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px}.rpt-head h1,.rpt-section h2{margin:0}.rpt-muted{color:#64748b}.rpt-filter{display:flex;align-items:end;gap:12px;flex-wrap:wrap;margin-bottom:18px}.rpt-field{display:grid;gap:6px}.rpt-field span{font-size:12px;font-weight:700;color:#475569}.rpt-field input{min-height:38px;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#172944}.rpt-error{color:#b42318;font-size:12px}.rpt-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:18px}.rpt-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:16px}.rpt-stat{padding:15px;border:1px solid #e5e9f2;border-radius:10px;background:#f8fafc}.rpt-stat strong{display:block;font-size:24px;color:#172944}.rpt-stat span{font-size:12px;color:#64748b;text-transform:capitalize}.rpt-section{margin-bottom:18px}.rpt-section-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}.rpt-table-wrap{overflow:auto}.rpt-table{width:100%;border-collapse:collapse;font-size:14px}.rpt-table th,.rpt-table td{padding:11px 8px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}.rpt-table th{font-size:12px;text-transform:uppercase;color:#64748b;white-space:nowrap}.rpt-status{display:inline-block;border-radius:999px;padding:4px 8px;background:#eef2ff;color:#334155;text-transform:capitalize;white-space:nowrap}.rpt-profile-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.rpt-profile h3{margin:0 0 5px}.rpt-profile-total{font-size:25px;font-weight:800;color:#172944}.rpt-profile dl{margin:13px 0 0}.rpt-profile dl div{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-top:1px solid #edf0f5}.rpt-profile dt{text-transform:capitalize;color:#64748b}.rpt-profile dd{margin:0;font-weight:700}@media(max-width:1100px){.rpt-profile-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:760px){.rpt-grid,.rpt-profile-grid{grid-template-columns:1fr}.rpt-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.rpt-head{display:block}}
</style>

<div class="rpt-head">
    <div>
        <h1>Reports</h1>
        <p class="rpt-muted" style="margin:6px 0 0">Read-only reporting from persisted application records.</p>
    </div>
    <span class="rpt-muted">
        @if($filters['from'] || $filters['to'])
            Booking and payment activity:
            {{ $filters['from'] ? \Illuminate\Support\Carbon::parse($filters['from'])->format('M j, Y') : 'earliest' }}
            to
            {{ $filters['to'] ? \Illuminate\Support\Carbon::parse($filters['to'])->format('M j, Y') : 'present' }}
        @else
            Booking and payment activity: all time
        @endif
    </span>
</div>

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
                <div class="rpt-profile-total">{{ $summary['total'] }} <span class="rpt-muted" style="font-size:12px;font-weight:400">total</span></div>
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
