@extends('layouts.admin')
@section('title', 'Create Role')
@section('content')
<h1>Create Role</h1>
<form method="POST" action="{{ route('admin.roles.store') }}" class="egh-card">@csrf
<p><label>Role Name<br><input name="name" value="{{ old('name') }}" required></label></p>
<h3>Permissions</h3>
@foreach($permissions as $permission)<label style="display:block;margin:7px 0"><input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, old('permissions', []), true))> {{ $permission->name }}</label>@endforeach
<button class="egh-button" type="submit">Create Role</button> <a class="egh-button secondary" href="{{ route('admin.roles.index') }}">Cancel</a>
</form>
@endsection
