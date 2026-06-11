<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if($content->id)
        <meta name="pilot-content-id" content="{{ $content->id }}">
    @endif
    <title>{{ $content->meta['meta_title'] ?? $content->name }}</title>
    @if(! empty($content->meta['meta_description']))
        <meta name="description" content="{{ $content->meta['meta_description'] }}">
    @endif
    @if(! empty($content->meta['canonical_url']))
        <link rel="canonical" href="{{ $content->meta['canonical_url'] }}">
    @endif
    @if(! empty($content->meta['noindex']))
        <meta name="robots" content="noindex,nofollow">
    @endif
    @if(! empty($content->meta['og_image']))
        <meta property="og:image" content="{{ pilotAsset($content->meta['og_image']) }}">
    @endif
</head>
<body @if($content->id) data-pilot-content-id="{{ $content->id }}" @endif>
    @include('pilot::blocks', ['blocks' => $blocks])
    @includeIf('pilot::editor-bridge')
    @includeIf('pilot::in-context')
</body>
</html>
