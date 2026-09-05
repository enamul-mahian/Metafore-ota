@extends('layouts.admin')

@section('title', $agent->name)

@section('content')
<x-admin.page-header :title="$agent->name" description="Review this operational agent profile." icon="A" eyebrow="Agent details">
    <a class="egh-button secondary" href="{{ route('admin.agents.index') }}">Back</a>
    @can('agents.manage')<a class="egh-button" href="{{ route('admin.agents.edit', $agent) }}">Edit</a>@endcan
</x-admin.page-header>
<div class="egh-card">
    <dl class="agt-show-list">
        <dt>Email</dt><dd>{{ $agent->email }}</dd>
        <dt>Phone</dt><dd>{{ $agent->phone ?: 'Not specified' }}</dd>
        <dt>Company</dt><dd>{{ $agent->company_name ?: 'Not specified' }}</dd>
        <dt>Registration number</dt><dd>{{ $agent->registration_number ?: 'Not specified' }}</dd>
        <dt>Country</dt><dd>{{ $agent->country ? $agent->country->name.' ('.$agent->country->iso2.')' : 'Not specified' }}</dd>
        <dt>Status</dt><dd>{{ ucfirst($agent->status) }}</dd>
        <dt>Created</dt><dd>{{ $agent->created_at?->format('M j, Y g:i A') }}</dd>
        <dt>Notes</dt><dd class="agt-notes">{{ $agent->notes ?: 'None' }}</dd>
    </dl>
    @can('agents.manage')
        <form method="POST" action="{{ route('admin.agents.destroy', $agent) }}" class="admin-danger-zone" onsubmit="return confirm('Delete this agent profile?')">
            @csrf
            @method('DELETE')
            <button class="egh-button danger" type="submit">Delete Agent</button>
        </form>
    @endcan
</div>
@endsection
