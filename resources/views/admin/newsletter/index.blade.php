@extends('layouts.admin')

@section('title', 'Newsletter Subscribers')

@section('content')

<x-admin.page-header title="Newsletter Subscribers" description="Email addresses collected from the website newsletter form." icon="N" eyebrow="Customer leads">
    <span class="admin-status-badge">{{ $subscribedCount }} subscribed</span>
    @can('enquiries.manage')
        <a class="egh-button" href="{{ route('admin.newsletter.export', array_filter(['status' => $filters['status']])) }}">Export CSV</a>
    @endcan
</x-admin.page-header>

<form method="GET" class="egh-card agt-filter">
    <input name="search" value="{{ $filters['search'] }}" placeholder="Email address">
    <select name="status" aria-label="Status">
        <option value="">All statuses</option>
        @foreach($statuses as $status)
            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <button class="egh-button" type="submit">Filter</button>
</form>

<div class="egh-card agt-table-wrap">
    <table class="agt-table">
        <thead><tr><th>Email</th><th>Status</th><th>Source</th><th>Subscribed</th></tr></thead>
        <tbody>
        @forelse($subscribers as $subscriber)
            <tr>
                <td>{{ $subscriber->email }}</td>
                <td class="agt-status">{{ $subscriber->status }}</td>
                <td>{{ $subscriber->source ?: '—' }}</td>
                <td>{{ $subscriber->created_at?->format('M j, Y g:i A') }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No newsletter subscribers yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if ($subscribers->hasPages())<div class="admin-pagination">{{ $subscribers->links() }}</div>@endif
@endsection
