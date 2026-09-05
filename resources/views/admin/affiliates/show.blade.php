@extends('layouts.admin')
@section('title', $affiliate->name)
@section('content')
<x-admin.page-header :title="$affiliate->name" description="Review this operational affiliate profile." icon="A" eyebrow="Affiliate details"><a class="egh-button secondary" href="{{ route('admin.affiliates.index') }}">Back</a> @can('affiliates.manage')<a class="egh-button" href="{{ route('admin.affiliates.edit', $affiliate) }}">Edit</a>@endcan</x-admin.page-header>
<div class="egh-card">
<dl class="af-list"><dt>Email</dt><dd>{{ $affiliate->email }}</dd><dt>Phone</dt><dd>{{ $affiliate->phone ?: 'Not specified' }}</dd><dt>Organization</dt><dd>{{ $affiliate->organization_name ?: 'Not specified' }}</dd><dt>Referral code</dt><dd>{{ $affiliate->referral_code }}</dd><dt>Website</dt><dd>@if($affiliate->website_url)<a href="{{ $affiliate->website_url }}" rel="noopener noreferrer">{{ $affiliate->website_url }}</a>@else Not specified @endif</dd><dt>Country</dt><dd>{{ $affiliate->country ? $affiliate->country->name.' ('.$affiliate->country->iso2.')' : 'Not specified' }}</dd><dt>Status</dt><dd>{{ ucfirst($affiliate->status) }}</dd><dt>Notes</dt><dd class="af-notes">{{ $affiliate->notes ?: 'None' }}</dd></dl>
@can('affiliates.manage')<form method="POST" action="{{ route('admin.affiliates.destroy', $affiliate) }}" class="admin-danger-zone" onsubmit="return confirm('Delete this affiliate profile?')">@csrf @method('DELETE')<button class="egh-button danger">Delete Affiliate</button></form>@endcan</div>
@endsection
