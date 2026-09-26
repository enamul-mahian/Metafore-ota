@extends('layouts.admin')

@section('title', 'Edit '.$destination->name().' Work Visa')

@section('content')
<x-admin.page-header :title="'Edit '.$destination->name().' Work Visa'" description="Update this work visa destination." icon="W" eyebrow="Work visa">
    <a class="egh-button secondary" href="{{ route('admin.work-visa.destinations.index') }}">Back</a>
</x-admin.page-header>
<div class="egh-card">
    <form method="POST" action="{{ route('admin.work-visa.destinations.update', $destination) }}">
        @csrf
        @method('PATCH')
        @include('admin.work-visa.destinations._form', ['submitLabel' => 'Save Changes'])
    </form>

    <form method="POST" action="{{ route('admin.work-visa.destinations.destroy', $destination) }}" class="admin-danger-zone" onsubmit="return confirm('Delete this work visa destination?')">
        @csrf
        @method('DELETE')
        <button class="egh-button danger" type="submit">Delete Destination</button>
    </form>
</div>
@endsection
