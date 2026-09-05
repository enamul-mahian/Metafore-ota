@extends('layouts.admin')
@section('title', 'Edit Student')
@section('content')
<style>.stu-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.stu-form-grid label{display:grid;gap:6px;font-weight:600}.stu-form-grid input,.stu-form-grid select,.stu-form-grid textarea{border:1px solid #cbd5e1;border-radius:8px;padding:10px}.stu-form-grid textarea{min-height:120px}.stu-full{grid-column:1/-1}@media(max-width:700px){.stu-form-grid{grid-template-columns:1fr}.stu-full{grid-column:auto}}</style>
<div class="egh-card"><h1 style="margin-top:0">Edit Student</h1><form method="POST" action="{{ route('admin.students.update', $student) }}">@csrf @method('PATCH') @include('admin.students._form', ['submitLabel' => 'Save Changes'])</form></div>
@endsection
