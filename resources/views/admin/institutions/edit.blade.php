@extends('layouts.admin')
@section('title', 'Edit Institution')
@section('content')
<style>.ins-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.ins-form-grid label{display:grid;gap:6px;font-weight:600}.ins-form-grid input,.ins-form-grid select,.ins-form-grid textarea{border:1px solid #cbd5e1;border-radius:8px;padding:10px}.ins-form-grid textarea{min-height:90px}.ins-full{grid-column:1/-1}@media(max-width:700px){.ins-form-grid{grid-template-columns:1fr}.ins-full{grid-column:auto}}</style><div class="egh-card"><h1 style="margin-top:0">Edit Institution</h1><form method="POST" action="{{ route('admin.institutions.update', $institution) }}">@csrf @method('PATCH') @include('admin.institutions._form', ['submitLabel' => 'Save Changes'])</form></div>
@endsection
