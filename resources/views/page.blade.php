<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-zinc-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if($content->id)
        <meta name="pilot-content-id" content="{{ $content->id }}">
    @endif
    <title>{{ $content->meta['meta_title'] ?? $content->name }} · {{ config('app.name') }}</title>
    @if(! empty($content->meta['meta_description']))
        <meta name="description" content="{{ $content->meta['meta_description'] }}">
    @endif
    @if(! empty($content->meta['canonical_url']))
        <link rel="canonical" href="{{ $content->meta['canonical_url'] }}">
    @endif
    @if(! empty($content->meta['noindex']))
        <meta name="robots" content="noindex,nofollow">
    @endif
    <meta property="og:title" content="{{ $content->meta['meta_title'] ?? $content->name }}">
    @if(! empty($content->meta['meta_description']))
        <meta property="og:description" content="{{ $content->meta['meta_description'] }}">
    @endif
    @if(! empty($content->meta['og_image']))
        <meta property="og:image" content="{{ pilotAsset($content->meta['og_image']) }}">
    @endif
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-zinc-50 text-zinc-950 antialiased" @if($content->id) data-pilot-content-id="{{ $content->id }}" @endif>
    <header class="border-b border-zinc-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-6 px-5 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="text-sm font-semibold tracking-wide text-zinc-950">{{ $space?->name ?? config('app.name') }}</a>
            <nav class="flex items-center gap-4 text-sm text-zinc-600">
                <a class="hover:text-zinc-950" href="{{ route('home') }}">Home</a>
                <a class="hover:text-zinc-950" href="{{ url('/about-us') }}">About</a>
                <a class="hover:text-zinc-950" href="{{ url('/product-1') }}">Product</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto grid w-full max-w-6xl gap-8 px-5 py-10 sm:px-6">
        @include('pilot::blocks', ['blocks' => $blocks])
    </main>

    @includeIf('pilot::editor-bridge')
    @includeIf('pilot::in-context')
</body>
</html>
