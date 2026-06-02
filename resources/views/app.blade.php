<!doctype html>
@php
    $settings = app(\App\Shared\SystemSettings\Services\SystemSettingService::class);
    $publicSettings = $settings->values(true);
    $applicationName = $settings->string('general.application_name', 'Onlyvo');
    $applicationDescription = $settings->string('general.application_description', 'Onlyvo tenant platform');
    $metaTitle = $settings->string('seo.default_meta_title', $applicationName) ?: $applicationName;
    $metaDescription = $settings->string('seo.default_meta_description', $applicationDescription) ?: $applicationDescription;
    $favicon = $settings->string('general.favicon') ?: '/favicon.ico';
    $openGraphImage = $settings->string('seo.open_graph_image');
    $canonicalDomain = rtrim($settings->string('seo.canonical_domain'), '/');
    $canonicalUrl = $canonicalDomain ? (str_starts_with($canonicalDomain, 'http') ? $canonicalDomain : 'https://'.$canonicalDomain).request()->getRequestUri() : null;
    $allowIndexing = $settings->boolean('seo.allow_search_engine_indexing', true);
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $metaTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <meta name="robots" content="{{ $allowIndexing ? 'index,follow' : 'noindex,nofollow' }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        @if ($openGraphImage)
            <meta property="og:image" content="{{ $openGraphImage }}">
        @endif
        @if ($canonicalUrl)
            <link rel="canonical" href="{{ $canonicalUrl }}">
        @endif
        @if ($favicon)
            <link rel="icon" href="{{ $favicon }}">
        @endif
        <script>
            window.__SYSTEM_SETTINGS__ = @json($publicSettings);
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
