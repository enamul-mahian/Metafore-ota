@extends('layouts.admin')

@section('title', 'User Details')

@section('content')
    <x-admin.page-header
        :title="$user->name"
        description="Review this account's identity, role, and verification status."
        icon="U"
        eyebrow="User details"
    >
        @can('users.manage')
            @if (auth()->user()->hasRole('super-admin') || ! $user->hasRole('super-admin'))
                <a class="egh-button" href="{{ route('admin.users.edit', $user) }}">Edit User</a>
            @endif
        @endcan
    </x-admin.page-header>

    <section class="egh-card">
        <dl class="admin-detail-list">
            <dt>Email</dt><dd>{{ $user->email }}</dd>
            <dt>Role</dt><dd><span class="admin-status-badge">{{ $user->getRoleNames()->join(', ') ?: 'No role' }}</span></dd>
            <dt>Verified</dt><dd>{{ $user->email_verified_at ? 'Yes' : 'No' }}</dd>
            <dt>Created</dt><dd>{{ $user->created_at?->format('d M Y H:i') }}</dd>
        </dl>
    </section>

    @can('users.manage')
        @if (! auth()->user()->is($user) && ! $user->hasRole('super-admin'))
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="admin-danger-zone" onsubmit="return confirm('Delete this user?')">
                @csrf
                @method('DELETE')
                <button class="egh-button danger" type="submit">Delete User</button>
            </form>
        @endif
    @endcan
@endsection
