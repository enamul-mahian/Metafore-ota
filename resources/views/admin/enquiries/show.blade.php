@extends('layouts.admin')

@section('title', 'Enquiry #'.$enquiry->id)

@section('content')
<x-admin.page-header :title="'Enquiry #'.$enquiry->id" :description="$enquiry->serviceLabel().' from '.$enquiry->name" icon="E" eyebrow="Customer leads">
    <a class="egh-button secondary" href="{{ route('admin.enquiries.index') }}">Back</a>
    <a class="egh-button" href="mailto:{{ $enquiry->email }}">Email customer</a>
</x-admin.page-header>

<div class="egh-card">
    <dl class="agt-show-list">
        <dt>Service</dt><dd>{{ $enquiry->serviceLabel() }}</dd>
        <dt>Status</dt><dd>{{ $enquiry->statusLabel() }}</dd>
        <dt>Name</dt><dd>{{ $enquiry->name }}</dd>
        <dt>Email</dt><dd><a class="agt-link" href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd>
        <dt>Phone</dt><dd>@if($enquiry->phone)<a class="agt-link" href="tel:{{ preg_replace('/[^0-9+]/', '', $enquiry->phone) }}">{{ $enquiry->phone }}</a>@else Not specified @endif</dd>
        @foreach($enquiry->labelledDetails() as $detail)
            <dt>{{ $detail['label'] }}</dt><dd>{{ $detail['value'] }}</dd>
        @endforeach
        <dt>Message</dt><dd class="agt-notes">{{ $enquiry->message ?: 'None' }}</dd>
        <dt>Account</dt><dd>{{ $enquiry->user ? $enquiry->user->name.' ('.$enquiry->user->email.')' : 'Guest' }}</dd>
        <dt>Sent from</dt><dd>{{ $enquiry->source_url ?: 'Not recorded' }}</dd>
        <dt>Received</dt><dd>{{ $enquiry->created_at?->format('M j, Y g:i A') }}</dd>
        <dt>Last updated</dt><dd>{{ $enquiry->updated_at?->format('M j, Y g:i A') }}</dd>
    </dl>
</div>

@can('enquiries.manage')
    <div class="egh-card">
        <form method="POST" action="{{ route('admin.enquiries.update', $enquiry) }}">
            @csrf
            @method('PATCH')
            @if($errors->any())
                <div class="egh-alert is-error">
                    <ul class="admin-error-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="agt-form-grid">
                <label>Status
                    <select name="status" required>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $enquiry->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="agt-full">Internal note<textarea name="admin_note" maxlength="5000">{{ old('admin_note', $enquiry->admin_note) }}</textarea></label>
            </div>
            <div class="admin-form-actions">
                <button class="egh-button" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
@else
    <div class="egh-card">
        <dl class="agt-show-list">
            <dt>Internal note</dt><dd class="agt-notes">{{ $enquiry->admin_note ?: 'None' }}</dd>
        </dl>
    </div>
@endcan
@endsection
