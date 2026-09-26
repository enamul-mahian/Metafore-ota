@php
    $tabs = [
        'images' => ['label' => 'Images', 'route' => 'admin.website.images'],
        'destinations' => ['label' => 'Homepage destinations', 'route' => 'admin.website.destinations'],
        'seo' => ['label' => 'SEO', 'route' => 'admin.website.seo'],
        'tracking' => ['label' => 'Tracking & Ads', 'route' => 'admin.website.tracking'],
        'contact' => ['label' => 'Contact details', 'route' => 'admin.website.contact'],
    ];
@endphp

<nav class="wsc-tabs" aria-label="Website sections">
    @foreach ($tabs as $key => $tab)
        <a href="{{ route($tab['route']) }}" @class(['wsc-tab', 'is-active' => $activeTab === $key]) @if ($activeTab === $key) aria-current="page" @endif>{{ $tab['label'] }}</a>
    @endforeach
    <a href="{{ route('home') }}" class="wsc-tab wsc-tab--site" target="_blank" rel="noopener">View website ↗</a>
</nav>
<script>document.querySelector(".wsc-tab.is-active")?.scrollIntoView({ block: "nearest", inline: "center" });</script>

@if (session('status'))
    <div class="egh-alert wsc-alert" role="status">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="egh-alert is-error" role="alert">
        <ul class="admin-error-list">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@unless ($canManage)
    <div class="egh-alert wsc-alert">You can view these settings. Changing them needs the “content.manage” permission.</div>
@endunless
