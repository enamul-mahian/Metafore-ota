@extends('layouts.admin')

@section('title', 'Create Agent')

@section('content')
<style>.agt-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.agt-form-grid label{display:grid;gap:6px;font-weight:600}.agt-form-grid input,.agt-form-grid select,.agt-form-grid textarea{border:1px solid #cbd5e1;border-radius:8px;padding:10px;background:#fff}.agt-form-grid textarea{min-height:120px;resize:vertical}.agt-full{grid-column:1/-1}@media(max-width:700px){.agt-form-grid{grid-template-columns:1fr}.agt-full{grid-column:auto}}</style>
<div class="egh-card">
    <h1 style="margin-top:0">Create Agent</h1>
    <p>Agent profiles are operational records only; no commission or financial terms are implied.</p>
    <form method="POST" action="{{ route('admin.agents.store') }}">
        @csrf
        @include('admin.agents._form', ['submitLabel' => 'Create Agent'])
    </form>
</div>
@endsection
