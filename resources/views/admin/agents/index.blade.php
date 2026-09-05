@extends('layouts.admin')

@section('title', 'Agents')

@section('content')

<x-admin.page-header title="Agents" description="Manage operational agent profiles and contact details." icon="A" eyebrow="Business profiles">
    @can('agents.manage')<a class="egh-button" href="{{ route('admin.agents.create') }}">Create Agent</a>@endcan
</x-admin.page-header>

<form method="GET" class="egh-card agt-filter">
    <input name="search" value="{{ $filters['search'] }}" placeholder="Name, email, company or registration">
    <select name="status">
        <option value="">All statuses</option>
        @foreach($statuses as $status)
            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <button class="egh-button" type="submit">Filter</button>
</form>

<div class="egh-card agt-table-wrap">
    <table class="agt-table">
        <thead><tr><th>Name</th><th>Contact</th><th>Company</th><th>Country</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($agents as $agent)
            <tr>
                <td>{{ $agent->name }}</td>
                <td>{{ $agent->email }}@if($agent->phone)<br><span class="agt-muted">{{ $agent->phone }}</span>@endif</td>
                <td>{{ $agent->company_name ?: '—' }}</td>
                <td>{{ $agent->country ? $agent->country->name.' ('.$agent->country->iso2.')' : '—' }}</td>
                <td class="agt-status">{{ $agent->status }}</td>
                <td><a class="agt-link" href="{{ route('admin.agents.show', $agent) }}">View</a>@can('agents.manage') · <a class="agt-link" href="{{ route('admin.agents.edit', $agent) }}">Edit</a>@endcan</td>
            </tr>
        @empty
            <tr><td colspan="6">No agents found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if ($agents->hasPages())<div class="admin-pagination">{{ $agents->links() }}</div>@endif
@endsection
