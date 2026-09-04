@extends('layouts.admin')
@section('title', 'Role Details')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center"><div><h1>{{ $role->name }}</h1><p>{{ $role->users_count }} assigned user(s).</p></div>@can('roles.manage') @if($role->name !== 'super-admin') <a class="egh-button" href="{{ route('admin.roles.edit', $role) }}">Edit Role</a> @endif @endcan</div>
<div class="egh-card"><h3>Permissions</h3>@forelse($role->permissions as $permission)<span style="display:inline-block;padding:6px 10px;margin:4px;background:#eef3ff;border-radius:999px">{{ $permission->name }}</span>@empty<p>No permissions assigned.</p>@endforelse</div>
@can('roles.manage') @if(! in_array($role->name, ['super-admin','admin','customer'], true) && $role->users_count === 0)<form method="POST" action="{{ route('admin.roles.destroy', $role) }}" style="margin-top:20px" onsubmit="return confirm('Delete this role?')">@csrf @method('DELETE')<button class="egh-button" type="submit">Delete Role</button></form>@endif @endcan
@endsection
