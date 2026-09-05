@extends('layouts.admin')
@section('title', 'Edit Student')
@section('content')
<x-admin.page-header :title="'Edit '.$student->first_name.' '.$student->last_name" description="Update this private operational student profile." icon="S" eyebrow="Business profiles" />
<div class="egh-card"><form method="POST" action="{{ route('admin.students.update', $student) }}">@csrf @method('PATCH') @include('admin.students._form', ['submitLabel' => 'Save Changes'])</form></div>
@endsection
