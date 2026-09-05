@extends('layouts.admin')
@section('title', 'Create Student')
@section('content')
<x-admin.page-header title="Create Student" description="Create a private operational student profile." icon="S" eyebrow="Business profiles" />
<div class="egh-card"><p>This profile stores no passport, identity-document, admission, visa, enrollment, or financial data.</p><form method="POST" action="{{ route('admin.students.store') }}">@csrf @include('admin.students._form', ['submitLabel' => 'Create Student'])</form></div>
@endsection
