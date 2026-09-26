@extends('layouts.admin')

@section('title', 'Website SEO')

@section('content')
<x-admin.page-header title="Website SEO" description="Set the Google title and description of each public page. Leave a field empty to use the built-in text." icon="W" eyebrow="Website" />

@include('admin.website._tabs')

<form method="POST" action="{{ route('admin.website.seo.update') }}" class="wsc-seo-list">
    @csrf
    @method('PUT')

    @foreach ($seoPages as $page)
        @php
            $titleValue = old('pages.'.$page['route'].'.title', $page['title']);
            $descriptionValue = old('pages.'.$page['route'].'.description', $page['description']);
            $fieldId = 'seo-'.str_replace(['.', '-'], '_', $page['route']);
        @endphp
        <section class="egh-card wsc-seo" data-seo-item>
            <header class="wsc-seo__head">
                <h2>{{ $page['label'] }}</h2>
                @if ($page['url'])
                    <a href="{{ $page['url'] }}" target="_blank" rel="noopener">{{ parse_url($page['url'], PHP_URL_PATH) ?: '/' }} ↗</a>
                @else
                    <span class="wsc-muted">Page not available yet</span>
                @endif
            </header>
            <div class="wsc-seo__fields">
                <label for="{{ $fieldId }}-title">Meta title
                    <input id="{{ $fieldId }}-title" name="pages[{{ $page['route'] }}][title]" value="{{ $titleValue }}" maxlength="70" placeholder="{{ $page['default_title'] }}" data-seo-title @disabled(! $canManage)>
                    <small class="wsc-counter" data-counter data-limit="60"></small>
                </label>
                <label for="{{ $fieldId }}-description">Meta description
                    <textarea id="{{ $fieldId }}-description" name="pages[{{ $page['route'] }}][description]" maxlength="170" rows="2" placeholder="{{ $page['default_description'] }}" data-seo-description @disabled(! $canManage)>{{ $descriptionValue }}</textarea>
                    <small class="wsc-counter" data-counter data-limit="160"></small>
                </label>
            </div>
            <div class="wsc-serp" aria-label="Google preview">
                <span class="wsc-serp__url">{{ $page['url'] ?? url('/') }}</span>
                <span class="wsc-serp__title" data-preview-title>{{ $titleValue ?: $page['default_title'] }}</span>
                <span class="wsc-serp__desc" data-preview-description>{{ $descriptionValue ?: $page['default_description'] }}</span>
            </div>
        </section>
    @endforeach

    @if ($canManage)
        <div class="admin-form-actions wsc-sticky-actions">
            <button type="submit" class="egh-button">Save SEO</button>
        </div>
    @endif
</form>

<p class="wsc-hint">Search engines can also read <a href="{{ route('sitemap') }}" target="_blank" rel="noopener">sitemap.xml</a> and <a href="{{ route('robots') }}" target="_blank" rel="noopener">robots.txt</a>. Submit the sitemap in Google Search Console.</p>
@endsection

@section('scripts')
<script>
    document.querySelectorAll('[data-seo-item]').forEach(function (item) {
        var title = item.querySelector('[data-seo-title]');
        var description = item.querySelector('[data-seo-description]');
        var previewTitle = item.querySelector('[data-preview-title]');
        var previewDescription = item.querySelector('[data-preview-description]');
        function update() {
            [title, description].forEach(function (field) {
                var counter = field.parentElement.querySelector('[data-counter]');
                var length = (field.value || field.placeholder).length;
                var limit = Number(counter.dataset.limit);
                counter.textContent = length + ' / ' + limit + ' characters' + (field.value ? '' : ' (default)');
                counter.classList.toggle('is-over', length > limit);
            });
            previewTitle.textContent = title.value || title.placeholder;
            previewDescription.textContent = description.value || description.placeholder;
        }
        title.addEventListener('input', update);
        description.addEventListener('input', update);
        update();
    });
</script>
@endsection
