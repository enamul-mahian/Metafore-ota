@extends('layouts.admin')
@section('title', 'Affiliates')
@section('content')
<x-admin.page-header title="Affiliates" description="Manage affiliate partner profiles and referral identifiers." icon="A" eyebrow="Business profiles">
@can('affiliates.manage')<a class="egh-button" href="{{ route('admin.affiliates.create') }}">Create Affiliate</a>@endcan
</x-admin.page-header>
<form method="GET" class="egh-card af-filter"><input name="search" value="{{ $filters['search'] }}" placeholder="Name, email, organization or code"><select name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>@endforeach</select><button class="egh-button">Filter</button></form>
<div class="egh-card admin-table-card"><table class="af-table"><thead><tr><th>Name</th><th>Contact</th><th>Organization</th><th>Referral code</th><th>Country</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($affiliates as $affiliate)<tr><td>{{ $affiliate->name }}</td><td>{{ $affiliate->email }}@if($affiliate->phone)<br><span class="af-muted">{{ $affiliate->phone }}</span>@endif</td><td>{{ $affiliate->organization_name ?: '—' }}</td><td>{{ $affiliate->referral_code }}</td><td>{{ $affiliate->country ? $affiliate->country->name.' ('.$affiliate->country->iso2.')' : '—' }}</td><td>{{ ucfirst($affiliate->status) }}</td><td><a class="af-link" href="{{ route('admin.affiliates.show', $affiliate) }}">View</a>@can('affiliates.manage') · <a class="af-link" href="{{ route('admin.affiliates.edit', $affiliate) }}">Edit</a>@endcan</td></tr>@empty<tr><td colspan="7">No affiliates found.</td></tr>@endforelse
</tbody></table></div>@if ($affiliates->hasPages())<div class="admin-pagination">{{ $affiliates->links() }}</div>@endif
@endsection
