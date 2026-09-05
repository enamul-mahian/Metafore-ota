@extends('layouts.admin')
@section('title', 'Edit Institution')
@section('content')
<x-admin.page-header :title="'Edit '.$institution->name" description="Update this education institution contact profile." icon="I" eyebrow="Business profiles" />
<div class="egh-card"><form method="POST" action="{{ route('admin.institutions.update', $institution) }}">@csrf @method('PATCH') @include('admin.institutions._form', ['submitLabel' => 'Save Changes'])</form></div>
@endsection
