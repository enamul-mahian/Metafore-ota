@extends('layouts.admin')

@section('title', 'Create Agent')

@section('content')
<x-admin.page-header title="Create Agent" description="Create an operational agent contact profile." icon="A" eyebrow="Business profiles" />
<div class="egh-card">
    <p>Agent profiles are operational records only; no commission or financial terms are implied.</p>
    <form method="POST" action="{{ route('admin.agents.store') }}">
        @csrf
        @include('admin.agents._form', ['submitLabel' => 'Create Agent'])
    </form>
</div>
@endsection
