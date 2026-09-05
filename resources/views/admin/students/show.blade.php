@extends('layouts.admin')
@section('title', $student->first_name.' '.$student->last_name)
@section('content')
<x-admin.page-header :title="$student->first_name.' '.$student->last_name" description="Review this private operational student profile." icon="S" eyebrow="Student details"><a class="egh-button secondary" href="{{ route('admin.students.index') }}">Back</a> @can('students.manage')<a class="egh-button" href="{{ route('admin.students.edit', $student) }}">Edit</a>@endcan</x-admin.page-header>
<div class="egh-card">
<dl class="stu-show"><dt>Reference</dt><dd>{{ $student->reference_code }}</dd><dt>Email</dt><dd>{{ $student->email }}</dd><dt>Phone</dt><dd>{{ $student->phone ?: 'Not specified' }}</dd><dt>Country</dt><dd>{{ $student->country ? $student->country->name.' ('.$student->country->iso2.')' : 'Not specified' }}</dd><dt>Date of birth</dt><dd>{{ $student->date_of_birth?->format('M j, Y') ?? 'Not specified' }}</dd><dt>Status</dt><dd>{{ ucfirst($student->status) }}</dd><dt>Notes</dt><dd class="stu-notes">{{ $student->notes ?: 'None' }}</dd></dl>
@can('students.manage')<form method="POST" action="{{ route('admin.students.destroy', $student) }}" class="admin-danger-zone" onsubmit="return confirm('Delete this student profile?')">@csrf @method('DELETE')<button class="egh-button danger">Delete Student</button></form>@endcan</div>
@endsection
