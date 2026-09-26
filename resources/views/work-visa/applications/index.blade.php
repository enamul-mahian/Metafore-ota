@extends('layouts.site')

@section('title', 'My Work Visa Applications')
@section('seo_title', 'My Work Visa Applications | Eagle Global Hub')
@section('robots', 'noindex,nofollow')
@section('body_class', 'wv-body')

@section('content')
<section class="page">
    <div class="container">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>My Account</span><span>Work Visa Applications</span></nav>

        <div class="account-layout">
            @include('work-visa.partials.account-menu')

            <div>
                <div class="row row--between wv-head">
                    <h1 class="h1">Work Visa Applications</h1>
                    @can('work-visa.apply')
                        <a href="{{ route('work-visa.apply') }}" class="btn btn--blue"><svg class="ic"><use href="#i-plus"/></svg>New Application</a>
                    @endcan
                </div>

                @if ($applications->isEmpty())
                    <div class="card empty">
                        <svg class="ic"><use href="#i-briefcase"/></svg>
                        <p class="h3">No work visa applications yet</p>
                        <p>When you apply for a work visa, you can track its status and documents here.</p>
                        <a href="{{ route('work-visa.index') }}" class="btn btn--outline">Explore Work Visa Destinations</a>
                    </div>
                @else
                    <ul class="wv-list">
                        @foreach ($applications as $application)
                            <li class="booking wv-booking">
                                <span class="booking__ic wv-flag" aria-hidden="true">{{ $application->destination?->flag() }}</span>
                                <div>
                                    <b>{{ $application->destination?->name() ?? 'Work visa' }} – Work Visa</b>
                                    <small>Ref. {{ $application->reference }}</small>
                                </div>
                                <div>
                                    <b>{{ $application->job_category }}</b>
                                    <small>{{ $application->fullName() }}</small>
                                </div>
                                <span class="badge badge--{{ $application->status->badge() }}">{{ $application->status->label() }}</span>
                                <div>
                                    <small>Submitted</small>
                                    <b>{{ $application->created_at?->format('j M Y') }}</b>
                                </div>
                                <a href="{{ route('work-visa.applications.show', $application) }}" class="btn btn--outline btn--sm">View <span class="wv-sr">application {{ $application->reference }}</span></a>
                            </li>
                        @endforeach
                    </ul>

                    @if ($applications->hasPages())
                        <div class="wv-pagination">{{ $applications->links() }}</div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
