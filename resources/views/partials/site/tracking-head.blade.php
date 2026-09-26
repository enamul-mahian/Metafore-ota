@php
    $tracking = $site->tracking();
    $customCode = $site->customCode();
    $googleTagIds = array_values(array_filter([$tracking['google_analytics_id'], $tracking['google_ads_id']]));
@endphp

@if ($tracking['google_site_verification'] !== '')
    <meta name="google-site-verification" content="{{ $tracking['google_site_verification'] }}">
@endif
@if ($tracking['facebook_domain_verification'] !== '')
    <meta name="facebook-domain-verification" content="{{ $tracking['facebook_domain_verification'] }}">
@endif

@if ($tracking['google_tag_manager_id'] !== '')
    <script>
        (function (w, d, s, l, i) { w[l] = w[l] || []; w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' }); var f = d.getElementsByTagName(s)[0], j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : ''; j.async = true; j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl; f.parentNode.insertBefore(j, f); })(window, document, 'script', 'dataLayer', @json($tracking['google_tag_manager_id']));
    </script>
@endif

@if ($googleTagIds !== [])
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($googleTagIds[0]) }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        @foreach ($googleTagIds as $googleTagId)
            gtag('config', @json($googleTagId));
        @endforeach
    </script>
@endif

@if ($tracking['meta_pixel_id'] !== '')
    <script>
        !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments) }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s) }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($tracking['meta_pixel_id']));
        fbq('track', 'PageView');
    </script>
@endif

@if ($tracking['tiktok_pixel_id'] !== '')
    <script>
        !function (w, d, t) { w.TiktokAnalyticsObject = t; var ttq = w[t] = w[t] || []; ttq.methods = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie']; ttq.setAndDefer = function (t, e) { t[e] = function () { t.push([e].concat(Array.prototype.slice.call(arguments, 0))) } }; for (var i = 0; i < ttq.methods.length; i++) ttq.setAndDefer(ttq, ttq.methods[i]); ttq.load = function (e) { var s = d.createElement('script'); s.async = true; s.src = 'https://analytics.tiktok.com/i18n/pixel/events.js?sdkid=' + e + '&lib=' + t; d.head.appendChild(s) }; ttq.load(@json($tracking['tiktok_pixel_id'])); ttq.page(); }(window, document, 'ttq');
    </script>
@endif

{{-- Admin-supplied code (Admin > Website > Tracking & Ads, super-admin only). --}}
{!! $customCode['head'] !!}
