@extends('layouts.admin')
@section('title', 'Edit Role')
@section('content')
<h1>Edit Role</h1>
<form method="POST" action="{{ route('admin.roles.update', $role) }}" class="egh-card">@csrf @method('PATCH')
<p><label>Role Name<br><input name="name" value="{{ old('name', $role->name) }}" required @disabled($isSystemRole)></label>@if($isSystemRole)<input type="hidden" name="name" value="{{ $role->name }}">@endif</p>
<h3>Permissions</h3>
@foreach($permissions as $permission)<label style="display:block;margin:7px 0"><input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, old('permissions', $role->permissions->pluck('name')->all()), true))> {{ $permission->name }}</label>@endforeach
<button class="egh-button" type="submit">Save Role</button> <a class="egh-button secondary" href="{{ route('admin.roles.show', $role) }}">Cancel</a>
</form>
@endsection
