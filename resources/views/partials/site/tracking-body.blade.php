@php
    $tracking = $site->tracking();
    $customCode = $site->customCode();
@endphp

@if ($tracking['google_tag_manager_id'] !== '')
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode($tracking['google_tag_manager_id']) }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif

@if ($tracking['meta_pixel_id'] !== '')
    <noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ urlencode($tracking['meta_pixel_id']) }}&ev=PageView&noscript=1"></noscript>
@endif

{!! $customCode['body'] !!}
