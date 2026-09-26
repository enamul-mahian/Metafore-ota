@extends('layouts.admin')

@section('title', 'Contact Details')

@section('content')
<x-admin.page-header title="Contact Details" description="Phone, email, address and social links shown in the website header, footer and contact page. Empty fields are hidden." icon="W" eyebrow="Website" />

@include('admin.website._tabs')

<form method="POST" action="{{ route('admin.website.contact.update') }}" class="egh-card wsc-section">
    @csrf
    @method('PUT')
    <div class="wsc-form-grid">
        @foreach ($contactFields as $field => $label)
            @php
                $type = match (true) {
                    $field === 'email' => 'email',
                    in_array($field, ['phone', 'whatsapp'], true) => 'tel',
                    str_ends_with($field, '_url') => 'url',
                    default => 'text',
                };
            @endphp
            <label for="contact-{{ $field }}" @class(['wsc-full' => $field === 'address'])>{{ $label }}
                <input id="contact-{{ $field }}" type="{{ $type }}" name="{{ $field }}" value="{{ old($field, $contact[$field] ?? '') }}" @if ($type === 'url') placeholder="https://" @endif @disabled(! $canManage)>
            </label>
        @endforeach
    </div>
    @if ($canManage)
        <div class="admin-form-actions">
            <button type="submit" class="egh-button">Save contact details</button>
        </div>
    @endif
</form>
@endsection
