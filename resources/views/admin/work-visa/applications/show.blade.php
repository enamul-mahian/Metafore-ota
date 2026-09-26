@extends('layouts.admin')

@section('title', 'Work Visa '.$application->reference)

@section('content')
<x-admin.page-header :title="'Work Visa '.$application->reference" description="Applicant details, documents and status history." icon="W" eyebrow="Work visa">
    <a class="egh-button secondary" href="{{ route('admin.work-visa.applications.index') }}">Back</a>
</x-admin.page-header>

@if($errors->any())
    <div class="egh-alert is-error">
        <ul class="admin-error-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="egh-card">
    <div class="abd-grid">
        <section class="abd-panel">
            <h2>Application</h2>
            <dl class="abd-list">
                <dt>Reference</dt><dd>{{ $application->reference }}</dd>
                <dt>Status</dt><dd><span class="agt-status">{{ $application->status->label() }}</span></dd>
                <dt>Destination</dt><dd>{{ $application->destination?->flag() }} {{ $application->destination?->name() ?? '—' }}</dd>
                <dt>Visa type</dt><dd>{{ $application->destination?->visa_type ?: '—' }}</dd>
                <dt>Job category</dt><dd>{{ $application->job_category }}</dd>
                <dt>Experience</dt><dd>{{ $application->experience_years !== null ? $application->experience_years.' years' : 'Not specified' }}</dd>
                <dt>Submitted</dt><dd>{{ $application->created_at?->format('M j, Y g:i A') }}</dd>
                <dt>Account</dt><dd>{{ $application->user?->name }} ({{ $application->user?->email }})</dd>
            </dl>
        </section>

        <section class="abd-panel">
            <h2>Applicant</h2>
            <dl class="abd-list">
                <dt>Name</dt><dd>{{ $application->fullName() }}</dd>
                <dt>Date of birth</dt><dd>{{ $application->date_of_birth?->format('M j, Y') }}</dd>
                <dt>Gender</dt><dd>{{ ucfirst($application->gender) }}</dd>
                <dt>Nationality</dt><dd>{{ $application->nationality }}</dd>
                <dt>Passport</dt><dd>{{ $application->passport_number }} (expires {{ $application->passport_expiry?->format('M j, Y') }})</dd>
                <dt>Email</dt><dd>{{ $application->email }}</dd>
                <dt>Phone</dt><dd>{{ $application->phone }}</dd>
                <dt>Address</dt><dd>{{ $application->current_address }}</dd>
                <dt>Applicant notes</dt><dd class="agt-notes">{{ $application->notes ?: 'None' }}</dd>
            </dl>
        </section>
    </div>
</div>

<div class="egh-card">
    <h2>Documents</h2>
    <div class="agt-table-wrap">
        <table class="agt-table">
            <thead><tr><th>Type</th><th>File</th><th>Size</th><th>Uploaded</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($application->documents as $document)
                <tr>
                    <td>{{ $document->typeLabel() }}</td>
                    <td>{{ $document->original_name }}<br><span class="agt-muted">{{ $document->mime }}</span></td>
                    <td>{{ $document->humanSize() }}</td>
                    <td>{{ $document->created_at?->format('M j, Y g:i A') }}</td>
                    <td><a class="agt-link" href="{{ route('work-visa.documents.download', $document) }}">Download</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No documents uploaded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('work-visa.manage')
    <div class="egh-card">
        <h2>Update status</h2>
        <form method="POST" action="{{ route('admin.work-visa.applications.status', $application) }}">
            @csrf
            @method('PATCH')
            <div class="agt-form-grid">
                <label>Status
                    <select name="status" required>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $application->status->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="agt-full">Note (visible to the applicant in their status history)<textarea name="note" maxlength="2000">{{ old('note') }}</textarea></label>
                <label class="agt-full" style="flex-direction:row;align-items:center;gap:10px"><input type="checkbox" name="notify_applicant" value="1" style="width:18px;height:18px;min-height:0" @checked(old('notify_applicant'))> Email the applicant about this change</label>
            </div>
            <div class="admin-form-actions">
                <button class="egh-button" type="submit">Save Status</button>
            </div>
        </form>
    </div>
@endcan

<div class="egh-card">
    <h2>Status history</h2>
    <div class="agt-table-wrap">
        <table class="agt-table">
            <thead><tr><th>Date</th><th>Status</th><th>Note</th><th>By</th></tr></thead>
            <tbody>
            @forelse($application->events->reverse() as $event)
                <tr>
                    <td>{{ $event->created_at?->format('M j, Y g:i A') }}</td>
                    <td><span class="agt-status">{{ $event->status->label() }}</span></td>
                    <td class="agt-notes">{{ $event->note ?: '—' }}</td>
                    <td>{{ $event->user?->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No status history.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
