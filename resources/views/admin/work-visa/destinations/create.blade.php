@extends('layouts.admin')

@section('title', 'Add Work Visa Destination')

@section('content')
<x-admin.page-header title="Add Work Visa Destination" description="Open a country for online work visa applications." icon="W" eyebrow="Work visa" />
<div class="egh-card">
    <p>Only enter fees and processing times you have confirmed. Leave the fee empty to show "On request".</p>
    <form method="POST" action="{{ route('admin.work-visa.destinations.store') }}">
        @csrf
        @include('admin.work-visa.destinations._form', ['submitLabel' => 'Create Destination'])
    </form>
</div>
@endsection
