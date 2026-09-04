@extends('layouts.admin')
@section('title', 'Roles & Permissions')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
<div><h1 style="margin:0">Roles &amp; Permissions</h1><p>Review and manage authorization roles.</p></div>
@can('roles.manage')<a class="egh-button" href="{{ route('admin.roles.create') }}">Create Role</a>@endcan
</div>
<div class="egh-card" style="overflow:auto"><table style="width:100%;border-collapse:collapse"><thead><tr><th align="left">Role</th><th align="left">Users</th><th align="left">Permissions</th><th align="left">Action</th></tr></thead><tbody>
@forelse($roles as $role)
<tr><td>{{ $role->name }}</td><td>{{ $role->users_count }}</td><td>{{ $role->permissions_count }}</td><td><a href="{{ route('admin.roles.show', $role) }}">View</a> @can('roles.manage') @if($role->name !== 'super-admin') · <a href="{{ route('admin.roles.edit', $role) }}">Edit</a> @endif @endcan</td></tr>
@empty
<tr><td colspan="4">No roles found.</td></tr>
@endforelse
</tbody></table></div>
@endsection
