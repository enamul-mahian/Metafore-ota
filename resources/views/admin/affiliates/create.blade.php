@extends('layouts.admin')
@section('title', 'Create Affiliate')
@section('content')
<x-admin.page-header title="Create Affiliate" description="Create an operational affiliate contact profile." icon="A" eyebrow="Business profiles" />
<div class="egh-card"><p>This profile does not create commission, balance, payout, or referral transaction records.</p><form method="POST" action="{{ route('admin.affiliates.store') }}">@csrf @include('admin.affiliates._form', ['submitLabel' => 'Create Affiliate'])</form></div>
@endsection
