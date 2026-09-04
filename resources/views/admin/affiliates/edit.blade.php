@extends('layouts.admin')
@section('title', 'Edit Affiliate')
@section('content')
<style>.af-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.af-form-grid label{display:grid;gap:6px;font-weight:600}.af-form-grid input,.af-form-grid select,.af-form-grid textarea{border:1px solid #cbd5e1;border-radius:8px;padding:10px;background:#fff}.af-form-grid textarea{min-height:120px}.af-full{grid-column:1/-1}@media(max-width:700px){.af-form-grid{grid-template-columns:1fr}.af-full{grid-column:auto}}</style>
<div class="egh-card"><h1 style="margin-top:0">Edit Affiliate</h1><form method="POST" action="{{ route('admin.affiliates.update', $affiliate) }}">@csrf @method('PATCH') @include('admin.affiliates._form', ['submitLabel' => 'Save Changes'])</form></div>
@endsection
