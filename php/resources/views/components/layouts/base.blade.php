@props([
    'title' => null,
    // Titel zonder " · SteynPT" erachter.
    'absoluteTitle' => false,
    'titleTemplate' => '%s · SteynPT',
    'description' => null,
    'noindex' => false,
])
@php
    $defaultTitle = 'SteynPT · Personal training, online coaching & ademcoaching in Amsterdam';
    $fullTitle = $title === null ? $defaultTitle : ($absoluteTitle ? $title : str_replace('%s', $title, $titleTemplate));
    $description ??= \App\Content\Defaults::SITE['description'];
    $siteUrl = rtrim(config('app.url'), '/');
@endphp
<!DOCTYPE html>
<html lang="nl" data-scroll-behavior="smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    @if ($noindex)
        <meta name="robots" content="noindex">
    @endif
    <meta name="theme-color" content="#ffffff">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:site_name" content="SteynPT">
    <meta property="og:locale" content="nl_NL">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ $siteUrl }}/images/steyn-glimlach.jpg">
    <link rel="icon" href="{{ asset('icon.jpg') }}" type="image/jpeg">
    <link rel="apple-touch-icon" href="{{ asset('apple-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-dvh flex-col">
    <a href="#inhoud" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-full focus:bg-accent-tint focus:px-4 focus:py-2 focus:text-ink">
        Naar de inhoud
    </a>
    {{ $slot }}
</body>
</html>
