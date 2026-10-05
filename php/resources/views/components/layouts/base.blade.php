@props([
    'title' => null,
    // Titel zonder " · SteynPT" erachter.
    'absoluteTitle' => false,
    'titleTemplate' => '%s · SteynPT',
    'description' => null,
    'noindex' => false,
    // Veelgestelde vragen op de pagina (voor de gestructureerde gegevens).
    'faq' => null,
])
@php
    $locale = \App\Site\Locale::current();
    $defaultTitle = __('SteynPT · Personal training en online coaching in Amsterdam');
    $fullTitle = $title === null ? $defaultTitle : ($absoluteTitle ? $title : str_replace('%s', $title, $titleTemplate));
    $description ??= __(\App\Content\Defaults::SITE['description']);
    $siteUrl = rtrim(config('app.url'), '/');
    // Vaste URL van deze pagina, zonder ?-parameters (bijv. /contact?onderwerp=…), voor zoekmachines.
    $path = trim(request()->path(), '/');
    $canonical = \App\Site\Site::url().'/'.$path;
    // Dezelfde pagina in de andere taal (hreflang), alleen voor openbare pagina's.
    $dutchPath = \App\Site\Locale::dutchPath($path);
    $alternates = $dutchPath === null ? [] : [
        'nl' => \App\Site\Site::url().$dutchPath,
        'en' => \App\Site\Site::url().\App\Site\Locale::path($dutchPath, 'en'),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" data-scroll-behavior="smooth">
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
    <meta property="og:locale" content="{{ $locale === 'en' ? 'en_GB' : 'nl_NL' }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ $siteUrl }}/images/og-steynpt.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ __('SteynPT, personal trainer bij Gymbase in Amsterdam Oud-West') }}">
    @unless ($noindex)
        <link rel="canonical" href="{{ $canonical }}">
        <meta property="og:url" content="{{ $canonical }}">
        @foreach ($alternates as $lang => $href)
            <link rel="alternate" hreflang="{{ $lang }}" href="{{ $href }}">
        @endforeach
        @if ($alternates)
            <link rel="alternate" hreflang="x-default" href="{{ $alternates['nl'] }}">
            <meta property="og:locale:alternate" content="{{ $locale === 'en' ? 'nl_NL' : 'en_GB' }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
        <script type="application/ld+json">{!! \App\Site\StructuredData::json(\App\Site\StructuredData::graph($canonical, $fullTitle, $description, $faq)) !!}</script>
    @endunless
    <link rel="icon" href="{{ asset('icon.jpg') }}" type="image/jpeg">
    <link rel="apple-touch-icon" href="{{ asset('apple-icon.png') }}">
    {{-- De twee lettertypen boven in beeld meteen ophalen, zodat de koppen niet verspringen (sneller scherm voor Google). --}}
    @foreach (['archivo/files/archivo-latin-wdth-normal.woff2', 'inter/files/inter-latin-wght-normal.woff2'] as $font)
        @php
            try {
                $fontUrl = \Illuminate\Support\Facades\Vite::asset("node_modules/@fontsource-variable/{$font}");
            } catch (\Throwable) {
                $fontUrl = null;
            }
        @endphp
        @if ($fontUrl)<link rel="preload" href="{{ $fontUrl }}" as="font" type="font/woff2" crossorigin>@endif
    @endforeach
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-dvh flex-col">
    <a href="#inhoud" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-full focus:bg-accent-tint focus:px-4 focus:py-2 focus:text-ink">
        {{ __('Naar de inhoud') }}
    </a>
    {{ $slot }}
</body>
</html>
