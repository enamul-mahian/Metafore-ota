@extends('layouts.admin')

@section('title', 'Work Visa Applications')

@section('content')

<x-admin.page-header title="Work Visa Applications" description="Review work visa applications, documents and status history." icon="W" eyebrow="Work visa">
    <span class="admin-status-badge">{{ $applications->total() }} total</span>
    <a class="egh-button secondary" href="{{ route('admin.work-visa.destinations.index') }}">Destinations</a>
</x-admin.page-header>

<form method="GET" class="egh-card agt-filter" aria-label="Filter work visa applications">
    <input name="search" value="{{ $filters['search'] }}" placeholder="Reference, name, passport or email" aria-label="Search">
    <select name="status" aria-label="Status">
        <option value="">All statuses</option>
        @foreach($statuses as $status)
            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <select name="destination" aria-label="Destination">
        <option value="">All destinations</option>
        @foreach($destinations as $destination)
            <option value="{{ $destination->id }}" @selected($filters['destination'] === (string) $destination->id)>{{ $destination->name() }}</option>
        @endforeach
    </select>
    <button class="egh-button" type="submit">Filter</button>
</form>

<div class="egh-card agt-table-wrap">
    <table class="agt-table">
        <thead><tr><th>Reference</th><th>Applicant</th><th>Destination</th><th>Job category</th><th>Status</th><th>Submitted</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($applications as $application)
            <tr>
                <td>{{ $application->reference }}</td>
                <td>{{ $application->fullName() }}<br><span class="agt-muted">Passport {{ $application->passport_number }}</span></td>
                <td>{{ $application->destination?->flag() }} {{ $application->destination?->name() ?? '—' }}</td>
                <td>{{ $application->job_category }}</td>
                <td><span class="agt-status">{{ $application->status->label() }}</span></td>
                <td>{{ $application->created_at?->format('M j, Y g:i A') }}</td>
                <td><a class="agt-link" href="{{ route('admin.work-visa.applications.show', $application) }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="7">No work visa applications found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if ($applications->hasPages())<div class="admin-pagination">{{ $applications->links() }}</div>@endif
@endsection
