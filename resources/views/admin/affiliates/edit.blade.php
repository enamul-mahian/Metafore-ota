@extends('layouts.admin')
@section('title', 'Edit Affiliate')
@section('content')
<x-admin.page-header :title="'Edit '.$affiliate->name" description="Update this operational affiliate profile." icon="A" eyebrow="Business profiles" />
<div class="egh-card"><form method="POST" action="{{ route('admin.affiliates.update', $affiliate) }}">@csrf @method('PATCH') @include('admin.affiliates._form', ['submitLabel' => 'Save Changes'])</form></div>
@endsection
