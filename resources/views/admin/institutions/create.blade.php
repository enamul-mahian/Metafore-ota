@extends('layouts.admin')
@section('title', 'Create Institution')
@section('content')
<x-admin.page-header title="Create Institution" description="Create an education institution contact profile." icon="I" eyebrow="Business profiles" />
<div class="egh-card"><p>This profile creates no admission, student assignment, contract, or financial workflow.</p><form method="POST" action="{{ route('admin.institutions.store') }}">@csrf @include('admin.institutions._form', ['submitLabel' => 'Create Institution'])</form></div>
@endsection
