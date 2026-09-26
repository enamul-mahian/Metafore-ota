@extends('layouts.admin')

@section('title', 'Work Visa Destinations')

@section('content')

<x-admin.page-header title="Work Visa Destinations" description="Countries open for online work visa applications, with processing times, fees and requirements." icon="W" eyebrow="Work visa">
    <a class="egh-button secondary" href="{{ route('admin.work-visa.applications.index') }}">Applications</a>
    @can('work-visa.manage')<a class="egh-button" href="{{ route('admin.work-visa.destinations.create') }}">Add Destination</a>@endcan
</x-admin.page-header>

<div class="egh-card agt-table-wrap">
    <table class="agt-table">
        <thead><tr><th>Country</th><th>Visa type</th><th>Processing time</th><th>Service fee</th><th>Applications</th><th>Status</th><th>Order</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($destinations as $destination)
            <tr>
                <td>{{ $destination->flag() }} {{ $destination->name() }}<br><span class="agt-muted">/{{ $destination->slug }}</span></td>
                <td>{{ $destination->visa_type ?: '—' }}</td>
                <td>{{ $destination->processing_time ?: '—' }}</td>
                <td>{{ $destination->formattedFee() ?? 'Not set' }}</td>
                <td>{{ $destination->applications_count }}</td>
                <td><span class="agt-status">{{ $destination->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>{{ $destination->sort_order }}</td>
                <td>@can('work-visa.manage')<a class="agt-link" href="{{ route('admin.work-visa.destinations.edit', $destination) }}">Edit</a>@else — @endcan</td>
            </tr>
        @empty
            <tr><td colspan="8">No work visa destinations yet. Add the countries you process work visas for; only active destinations appear on the website.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if ($destinations->hasPages())<div class="admin-pagination">{{ $destinations->links() }}</div>@endif
@endsection
