@props(['href', 'variant' => 'primary', 'size' => null])
{{-- Adressen van openbare pagina's krijgen in het Engels vanzelf hun Engelse versie (/en/…). --}}
<a href="{{ \App\Site\Locale::path($href) }}" {{ $attributes->class(['btn', "btn-{$variant}", 'btn-sm' => $size === 'sm']) }}>{{ $slot }}</a>
