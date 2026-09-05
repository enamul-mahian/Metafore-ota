@extends('layouts.admin')

@section('title', 'Feature Control')

@section('page-class', 'settings-page feature-control-page')

@section('content')
<x-admin.page-header title="Feature Control" description="Manage feature visibility within established provider and security boundaries." icon="F" eyebrow="System configuration">
    <span class="settings-access-mode">Super Admin only</span>
</x-admin.page-header>

            @if (session('status'))
                <div class="feature-control-alert" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="feature-control-alert is-error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <section class="feature-control-notice" aria-label="Safety boundary">
                <strong>Visibility is separate from provider activation.</strong>
                <p>
                    Enabling a feature exposes only its permitted website UI.
                    It does not configure suppliers, credentials, payments, or
                    live flight order execution.
                </p>
            </section>

            <section class="feature-control-list" aria-label="Registered features">
                <div class="feature-control-header" aria-hidden="true">
                    <span>Feature</span>
                    <span>Status</span>
                    <span>Public</span>
                    <span>Authenticated</span>
                    <span>Admin</span>
                    <span>Unavailable message</span>
                    <span>Actions</span>
                </div>

                @foreach ($features as $feature)
                    <form
                        method="POST"
                        action="{{ route('admin.features.update', $feature['key']) }}"
                        class="feature-control-row"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="feature-control-name">
                            <strong>{{ $feature['label'] }}</strong>
                            <code>{{ $feature['key'] }}</code>
                            <small>{{ $feature['description'] }}</small>
                        </div>

                        <label class="feature-control-toggle">
                            <input type="hidden" name="enabled" value="0">
                            <input
                                type="checkbox"
                                name="enabled"
                                value="1"
                                @checked($feature['enabled'])
                            >
                            <span @class(['is-enabled' => $feature['enabled']])>
                                {{ $feature['enabled'] ? 'Enabled' : 'Disabled' }}
                            </span>
                        </label>

                        @foreach ([
                            'public_visible' => 'Public visibility',
                            'authenticated_visible' => 'Authenticated visibility',
                            'admin_visible' => 'Admin visibility',
                        ] as $field => $label)
                            <label class="feature-control-check">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input
                                    type="checkbox"
                                    name="{{ $field }}"
                                    value="1"
                                    aria-label="{{ $feature['label'] }} {{ $label }}"
                                    @checked($feature[$field])
                                >
                                <span>{{ $feature[$field] ? 'Visible' : 'Hidden' }}</span>
                            </label>
                        @endforeach

                        <label class="feature-control-message">
                            <span class="sr-only">
                                {{ $feature['label'] }} unavailable message
                            </span>
                            <input
                                type="text"
                                name="message"
                                value="{{ $feature['message'] }}"
                                maxlength="500"
                                placeholder="This feature is currently unavailable."
                            >
                        </label>

                        <button type="submit" class="site-button site-button-primary">
                            Save
                        </button>
                    </form>
                @endforeach
            </section>
@endsection
