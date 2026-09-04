@extends('layouts.admin')
@section('title', 'Users')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
<div><h1 style="margin:0">Users</h1><p>Manage user accounts and roles.</p></div>
@can('users.manage')<a class="egh-button" href="{{ route('admin.users.create') }}">Create User</a>@endcan
</div>
<form method="GET" class="egh-card" style="margin-bottom:18px;display:flex;gap:10px;flex-wrap:wrap">
<input name="search" value="{{ $filters['search'] }}" placeholder="Search name or email">
<select name="role"><option value="">All roles</option>@foreach($roles as $roleName)<option value="{{ $roleName }}" @selected($filters['role'] === $roleName)>{{ $roleName }}</option>@endforeach</select>
<select name="verification"><option value="">All</option><option value="verified" @selected($filters['verification'] === 'verified')>Verified</option><option value="unverified" @selected($filters['verification'] === 'unverified')>Unverified</option></select>
<button class="egh-button" type="submit">Filter</button>
</form>
<div class="egh-card" style="overflow:auto"><table style="width:100%;border-collapse:collapse"><thead><tr><th align="left">Name</th><th align="left">Email</th><th align="left">Role</th><th align="left">Verified</th><th align="left">Action</th></tr></thead><tbody>
@forelse($users as $user)
<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->getRoleNames()->join(', ') ?: 'No role' }}</td><td>{{ $user->email_verified_at ? 'Yes' : 'No' }}</td><td><a href="{{ route('admin.users.show', $user) }}">View</a> @can('users.manage') @if(auth()->user()->hasRole('super-admin') || ! $user->hasRole('super-admin')) · <a href="{{ route('admin.users.edit', $user) }}">Edit</a> @endif @endcan</td></tr>
@empty
<tr><td colspan="5">No users found.</td></tr>
@endforelse
</tbody></table></div>
<div style="margin-top:16px">{{ $users->links() }}</div>
@endsection
