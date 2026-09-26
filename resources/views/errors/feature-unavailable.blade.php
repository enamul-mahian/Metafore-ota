@extends('layouts.site')

@section('title', 'Feature Unavailable')
@section('robots', 'noindex,follow')

@section('content')
<div class="container">
    <section class="err" aria-labelledby="feature-unavailable-title">
        <span class="svc__ic c-amber" style="margin:0 auto;width:64px;height:64px"><svg class="ic" style="width:30px;height:30px"><use href="#i-clock"/></svg></span>
        <h1 id="feature-unavailable-title">Feature unavailable</h1>
        <p>{{ $message }}</p>
        <div class="row">
            <a href="{{ route('home') }}" class="btn btn--blue">Return home</a>
            @if (Route::has('contact'))
                <a href="{{ route('contact') }}" class="btn btn--outline">Contact us</a>
            @endif
        </div>
    </section>
</div>
@endsection
