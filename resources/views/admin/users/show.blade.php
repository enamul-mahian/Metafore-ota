@extends('layouts.admin')
@section('title', 'User Details')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center"><div><h1>{{ $user->name }}</h1><p>User account details.</p></div>@can('users.manage') @if(auth()->user()->hasRole('super-admin') || ! $user->hasRole('super-admin')) <a class="egh-button" href="{{ route('admin.users.edit', $user) }}">Edit User</a> @endif @endcan</div>
<div class="egh-card"><p><strong>Email:</strong> {{ $user->email }}</p><p><strong>Role:</strong> {{ $user->getRoleNames()->join(', ') ?: 'No role' }}</p><p><strong>Verified:</strong> {{ $user->email_verified_at ? 'Yes' : 'No' }}</p><p><strong>Created:</strong> {{ $user->created_at?->format('d M Y H:i') }}</p></div>
@can('users.manage') @if(! auth()->user()->is($user) && ! $user->hasRole('super-admin')) <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="margin-top:20px" onsubmit="return confirm('Delete this user?')">@csrf @method('DELETE')<button class="egh-button" type="submit">Delete User</button></form> @endif @endcan
@endsection
