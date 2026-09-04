@extends('layouts.admin')

@section('title', 'Agents')

@section('content')
<style>
.agt-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px}.agt-filter{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px}.agt-filter input,.agt-filter select{border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;background:#fff}.agt-table-wrap{overflow:auto}.agt-table{width:100%;border-collapse:collapse;font-size:14px}.agt-table th,.agt-table td{padding:11px 8px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}.agt-table th{font-size:12px;text-transform:uppercase;color:#64748b}.agt-muted{color:#64748b}.agt-status{text-transform:capitalize}.agt-link{color:#244fc7;text-decoration:none;font-weight:600}
</style>

<div class="agt-head">
    <div><h1 style="margin:0">Agents</h1><p class="agt-muted">Manage operational agent profiles and contact details.</p></div>
    @can('agents.manage')<a class="egh-button" href="{{ route('admin.agents.create') }}">Create Agent</a>@endcan
</div>

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

<div style="margin-top:16px">{{ $agents->links() }}</div>
@endsection
