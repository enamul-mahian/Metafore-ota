@extends('layouts.admin')
@section('title', 'Students')
@section('content')
<x-admin.page-header title="Students" description="Manage private operational student profiles." icon="S" eyebrow="Business profiles">
@can('students.manage')<a class="egh-button" href="{{ route('admin.students.create') }}">Create Student</a>@endcan
</x-admin.page-header>
<form method="GET" class="egh-card stu-filter"><input name="search" value="{{ $filters['search'] }}" placeholder="Name, email or reference"><select name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>@endforeach</select><button class="egh-button">Filter</button></form>
<div class="egh-card admin-table-card"><table class="stu-table"><thead><tr><th>Reference</th><th>Name</th><th>Contact</th><th>Country</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($students as $student)<tr><td>{{ $student->reference_code }}</td><td>{{ $student->first_name }} {{ $student->last_name }}</td><td>{{ $student->email }}@if($student->phone)<br><span class="stu-muted">{{ $student->phone }}</span>@endif</td><td>{{ $student->country ? $student->country->name.' ('.$student->country->iso2.')' : '—' }}</td><td>{{ ucfirst($student->status) }}</td><td><a class="stu-link" href="{{ route('admin.students.show', $student) }}">View</a>@can('students.manage') · <a class="stu-link" href="{{ route('admin.students.edit', $student) }}">Edit</a>@endcan</td></tr>@empty<tr><td colspan="6">No students found.</td></tr>@endforelse
</tbody></table></div>@if ($students->hasPages())<div class="admin-pagination">{{ $students->links() }}</div>@endif
@endsection
