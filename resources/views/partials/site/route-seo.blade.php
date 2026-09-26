{{--
    SEO title/description for a route listed in config('site.seo_pages') whose
    name contains a dot (e.g. "hotels.index"): admin overrides first, then the
    config default read by array key (dot notation would split the name).
--}}
@php
    $routeSeoDefaults = config('site.seo_pages', [])[$seoRoute] ?? [];
    $routeSeo = $site->seoFor($seoRoute);
@endphp
@section('seo_title', $routeSeo['title'] ?: ($routeSeoDefaults['title'] ?? ''))
@section('seo_description', $routeSeo['description'] ?: ($routeSeoDefaults['description'] ?? ''))
