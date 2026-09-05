@extends('layouts.admin')

@section('title', 'System Logs')

@section('content')
<style>
.slog-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px}.slog-head h1,.slog-section h2{margin:0}.slog-muted{color:#64748b}.slog-notice{margin-bottom:18px;padding:15px 16px;border:1px solid #bfdbfe;border-radius:10px;background:#eff6ff;color:#1e3a5f;font-size:13px;line-height:1.55}.slog-filter{display:flex;align-items:end;gap:12px;flex-wrap:wrap;margin-bottom:18px}.slog-field{display:grid;gap:6px}.slog-field span{font-size:12px;font-weight:700;color:#475569}.slog-field select{min-width:190px;min-height:38px;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#172944}.slog-error{color:#b42318;font-size:12px}.slog-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:18px}.slog-stat{padding:16px}.slog-stat strong{display:block;font-size:24px;color:#172944}.slog-stat span{font-size:12px;color:#64748b;text-transform:capitalize}.slog-table-wrap{overflow:auto}.slog-table{width:100%;border-collapse:collapse;font-size:14px}.slog-table th,.slog-table td{padding:12px 9px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}.slog-table th{font-size:12px;text-transform:uppercase;color:#64748b;white-space:nowrap}.slog-level{display:inline-block;border-radius:999px;padding:4px 8px;background:#eef2ff;color:#334155;font-weight:700;text-transform:uppercase;white-space:nowrap}.slog-foot{margin:14px 0 0;font-size:12px;color:#64748b}@media(max-width:900px){.slog-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:620px){.slog-head{display:block}.slog-grid{grid-template-columns:1fr}}
</style>

<div class="slog-head">
    <div>
        <h1>System Logs</h1>
        <p class="slog-muted" style="margin:6px 0 0">Read-only metadata from recent persisted Laravel application log entries.</p>
    </div>
    <span class="slog-muted">{{ $logData['filesInspected'] }} local log {{ \Illuminate\Support\Str::plural('file', $logData['filesInspected']) }} inspected</span>
</div>

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
