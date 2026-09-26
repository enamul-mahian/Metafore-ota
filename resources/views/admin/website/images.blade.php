@extends('layouts.admin')

@section('title', 'Website Images')

@section('content')
<x-admin.page-header title="Website Images" description="Replace any photo on the public website. Uploads take effect immediately; Restore brings back the original." icon="W" eyebrow="Website" />

@include('admin.website._tabs')

@php
    $groupLabels = ['brand' => 'Brand', 'home' => 'Homepage', 'page' => 'Page banners', 'dest' => 'Destinations'];
@endphp

@foreach ($imageGroups as $group => $images)
    <section class="egh-card wsc-section">
        <h2 class="wsc-section__title">{{ $groupLabels[$group] ?? ucfirst($group) }}</h2>
        <div class="wsc-image-grid">
            @foreach ($images as $image)
                <article class="wsc-image">
                    <div class="wsc-image__pic">
                        <img src="{{ $image['url'] }}" alt="{{ $image['label'] }}" loading="lazy">
                        <span @class(['wsc-badge', 'is-custom' => $image['custom']])>{{ $image['custom'] ? 'Custom' : 'Default' }}</span>
                    </div>
                    <div class="wsc-image__body">
                        <strong>{{ $image['label'] }}</strong>
                        <code>{{ $image['key'] }}</code>
                        @if ($canManage)
                            <form method="POST" action="{{ route('admin.website.images.update', $image['key']) }}" enctype="multipart/form-data" class="wsc-image__form">
                                @csrf
                                <label class="wsc-file">
                                    <span>Choose image</span>
                                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
                                </label>
                                <button class="egh-button" type="submit">Upload</button>
                            </form>
                            @if ($image['custom'])
                                <form method="POST" action="{{ route('admin.website.images.reset', $image['key']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="egh-button secondary wsc-link-button" type="submit">Restore default</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endforeach

<p class="wsc-hint">JPG, PNG or WebP, up to {{ (int) (config('site.image_upload_max_kb') / 1024) }} MB. Wide banners look best at 1600×600 or larger.</p>
@endsection
