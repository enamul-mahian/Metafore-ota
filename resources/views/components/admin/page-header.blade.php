@props([
    'title',
    'description' => null,
    'icon' => null,
    'eyebrow' => 'Administration',
])

<header class="admin-page-heading">
    <div class="admin-page-title-wrap">
        <span class="admin-page-title-icon" aria-hidden="true">
            {{ $icon ?: mb_strtoupper(mb_substr($title, 0, 1)) }}
        </span>

        <div>
            <span class="admin-page-eyebrow">{{ $eyebrow }}</span>
            <h1>{{ $title }}</h1>

            @if ($description)
                <p>{{ $description }}</p>
            @endif
        </div>
    </div>

    <div class="admin-page-heading-side">
        <nav class="admin-page-breadcrumb" aria-label="Breadcrumb">
            @feature('dashboard')
                <a href="{{ route('dashboard') }}">Dashboard</a>
            @else
                <a href="{{ route('home') }}">Website</a>
            @endfeature
            <span aria-hidden="true">&rsaquo;</span>
            <strong>{{ $title }}</strong>
        </nav>

        @if ($slot->isNotEmpty())
            <div class="admin-page-actions">
                {{ $slot }}
            </div>
        @endif
    </div>
</header>
