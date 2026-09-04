@extends('layouts.admin')

@section('title', $agent->name)

@section('content')
<style>.agt-show-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:18px}.agt-show-list{display:grid;grid-template-columns:minmax(150px,1fr) 2fr;gap:12px;margin:0}.agt-show-list dt{color:#64748b}.agt-show-list dd{margin:0;word-break:break-word}.agt-actions{display:flex;gap:10px;flex-wrap:wrap}.agt-danger{background:#b91c1c}.agt-notes{white-space:pre-wrap}@media(max-width:650px){.agt-show-head{display:block}.agt-actions{margin-top:12px}.agt-show-list{grid-template-columns:1fr}}</style>
<div class="egh-card">
    <div class="agt-show-head">
        <div><h1 style="margin:0">{{ $agent->name }}</h1><p style="color:#64748b">Agent profile</p></div>
        <div class="agt-actions">
            <a class="egh-button secondary" href="{{ route('admin.agents.index') }}">Back</a>
            @can('agents.manage')<a class="egh-button" href="{{ route('admin.agents.edit', $agent) }}">Edit</a>@endcan
        </div>
    </div>
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
        <form method="POST" action="{{ route('admin.agents.destroy', $agent) }}" style="margin-top:22px" onsubmit="return confirm('Delete this agent profile?')">
            @csrf
            @method('DELETE')
            <button class="egh-button agt-danger" type="submit">Delete Agent</button>
        </form>
    @endcan
</div>
@endsection
