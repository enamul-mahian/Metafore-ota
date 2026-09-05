@extends('layouts.admin')
@section('title', 'Create Institution')
@section('content')
<style>.ins-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.ins-form-grid label{display:grid;gap:6px;font-weight:600}.ins-form-grid input,.ins-form-grid select,.ins-form-grid textarea{border:1px solid #cbd5e1;border-radius:8px;padding:10px}.ins-form-grid textarea{min-height:90px}.ins-full{grid-column:1/-1}@media(max-width:700px){.ins-form-grid{grid-template-columns:1fr}.ins-full{grid-column:auto}}</style><div class="egh-card"><h1 style="margin-top:0">Create Institution</h1><p>This profile creates no admission, student assignment, contract, or financial workflow.</p><form method="POST" action="{{ route('admin.institutions.store') }}">@csrf @include('admin.institutions._form', ['submitLabel' => 'Create Institution'])</form></div>
@endsection
