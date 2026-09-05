@extends('layouts.admin')

@section('title', 'System Logs')

@section('content')

<x-admin.page-header title="System Logs" description="Read-only metadata from recent persisted Laravel application log entries." icon="L" eyebrow="System information">
    <span class="admin-status-badge">{{ $logData['filesInspected'] }} local log {{ \Illuminate\Support\Str::plural('file', $logData['filesInspected']) }} inspected</span>
</x-admin.page-header>

<div class="slog-notice">
    <strong>Metadata-only security view.</strong>
    Raw messages, context, request data, exception details, and stack traces are withheld by design. This page shows application events; the application does not currently persist a user-action audit trail.
</div>

<form method="GET" action="{{ route('admin.system-logs.index') }}" class="egh-card slog-filter">
    <label class="slog-field">
        <span>Severity</span>
        <select name="level">
            <option value="">All severities</option>
            @foreach($levels as $level)
                <option value="{{ $level }}" @selected($filters['level'] === $level)>{{ ucfirst($level) }}</option>
            @endforeach
        </select>
        @error('level')<small class="slog-error">{{ $message }}</small>@enderror
    </label>
    <button type="submit" class="egh-button">Filter</button>
    @if($filters['level'])
        <a href="{{ route('admin.system-logs.index') }}" class="egh-button secondary">Clear</a>
    @endif
</form>

<div class="slog-grid">
    @foreach($logData['levelCounts'] as $level => $count)
        @if($count > 0)
            <div class="egh-card slog-stat">
                <strong>{{ $count }}</strong>
                <span>{{ $level }} in inspected sample</span>
            </div>
        @endif
    @endforeach
</div>

<section class="egh-card slog-section">
    <div class="slog-table-wrap">
        <table class="slog-table">
            <thead><tr><th>Recorded at</th><th>Severity</th><th>Redacted event summary</th></tr></thead>
            <tbody>
            @forelse($logData['entries'] as $entry)
                <tr>
                    <td>{{ $entry['timestamp'] }}</td>
                    <td><span class="slog-level">{{ $entry['level'] }}</span></td>
                    <td>{{ $entry['summary'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No matching application log metadata is available in the bounded recent sample.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="slog-foot">
        Showing up to 100 recent entries from bounded tails of up to 10 local Laravel log files.
        @if($logData['truncated']) Additional matching entries were withheld by the display limit. @endif
    </p>
</section>
@endsection
