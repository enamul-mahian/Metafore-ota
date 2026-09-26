@extends('layouts.site')

@section('title', 'Page Not Found')
@section('robots', 'noindex,follow')

@section('content')
<div class="container">
    <section class="err">
        <div class="err__code" aria-hidden="true">404</div>
        <h1>Page not found</h1>
        <p>The page you requested is not available. You can return home or continue through the currently available account services.</p>
        <div class="row">
            <a href="{{ route('home') }}" class="btn btn--blue">Home</a>
            @auth
                @feature('dashboard')
                    <a href="{{ route('dashboard') }}" class="btn btn--outline">Dashboard</a>
                @endfeature
            @else
                <a href="{{ route('login') }}" class="btn btn--outline">Login</a>
            @endauth
            @if (Route::has('contact'))
                <a href="{{ route('contact') }}" class="btn btn--outline">Contact us</a>
            @endif
        </div>
    </section>
</div>
@endsection
