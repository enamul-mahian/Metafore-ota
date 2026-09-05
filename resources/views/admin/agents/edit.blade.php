@extends('layouts.admin')

@section('title', 'Edit Agent')

@section('content')
<x-admin.page-header :title="'Edit '.$agent->name" description="Update this operational agent contact profile." icon="A" eyebrow="Business profiles" />
<div class="egh-card">
    <form method="POST" action="{{ route('admin.agents.update', $agent) }}">
        @csrf
        @method('PATCH')
        @include('admin.agents._form', ['submitLabel' => 'Save Changes'])
    </form>
</div>
@endsection
