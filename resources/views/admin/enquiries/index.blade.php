@extends('layouts.admin')

@section('title', 'Enquiries')

@section('content')

<x-admin.page-header title="Enquiries" description="Quote requests and contact messages sent from the public website." icon="E" eyebrow="Customer leads">
    <span class="admin-status-badge">{{ $newCount }} new</span>
</x-admin.page-header>

<form method="GET" class="egh-card agt-filter">
    <input name="search" value="{{ $filters['search'] }}" placeholder="Name, email, phone or message">
    <select name="service" aria-label="Service">
        <option value="">All services</option>
        @foreach($services as $value => $label)
            <option value="{{ $value }}" @selected($filters['service'] === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" aria-label="Status">
        <option value="">All statuses</option>
        @foreach($statuses as $value => $label)
            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="egh-button" type="submit">Filter</button>
</form>

<div class="egh-card agt-table-wrap">
    <table class="agt-table">
        <thead><tr><th>Received</th><th>Service</th><th>Contact</th><th>Summary</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($enquiries as $enquiry)
            @php($summary = collect($enquiry->labelledDetails())->take(2)->map(fn (array $row): string => $row['value'])->implode(' · '))
            <tr>
                <td>#{{ $enquiry->id }}<br><span class="agt-muted">{{ $enquiry->created_at?->format('M j, Y g:i A') }}</span></td>
                <td>{{ $enquiry->serviceLabel() }}</td>
                <td>{{ $enquiry->name }}<br><span class="agt-muted">{{ $enquiry->email }}@if($enquiry->phone) · {{ $enquiry->phone }}@endif</span></td>
                <td>{{ Str::limit($summary !== '' ? $summary : (string) $enquiry->message, 80) ?: '—' }}</td>
                <td class="agt-status">{{ $enquiry->status }}</td>
                <td><a class="agt-link" href="{{ route('admin.enquiries.show', $enquiry) }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="6">No enquiries found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if ($enquiries->hasPages())<div class="admin-pagination">{{ $enquiries->links() }}</div>@endif
@endsection
