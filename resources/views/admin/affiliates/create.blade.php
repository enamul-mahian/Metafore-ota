@extends('layouts.admin')
@section('title', 'Create Affiliate')
@section('content')
<style>.af-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.af-form-grid label{display:grid;gap:6px;font-weight:600}.af-form-grid input,.af-form-grid select,.af-form-grid textarea{border:1px solid #cbd5e1;border-radius:8px;padding:10px;background:#fff}.af-form-grid textarea{min-height:120px}.af-full{grid-column:1/-1}@media(max-width:700px){.af-form-grid{grid-template-columns:1fr}.af-full{grid-column:auto}}</style>
<div class="egh-card"><h1 style="margin-top:0">Create Affiliate</h1><p>This profile does not create commission, balance, payout, or referral transaction records.</p><form method="POST" action="{{ route('admin.affiliates.store') }}">@csrf @include('admin.affiliates._form', ['submitLabel' => 'Create Affiliate'])</form></div>
@endsection
