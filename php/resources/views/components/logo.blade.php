@props(['variant' => 'dark', 'class' => 'h-12 w-auto', 'priority' => false])
{{-- Het originele SteynPT-logo. "light" = wit logo voor donkere achtergronden. --}}
<img src="{{ asset($variant === 'light' ? 'brand/steynpt-logo-white.png' : 'brand/steynpt-logo-black.png') }}" alt="SteynPT" width="500" height="336" class="{{ $class }}" @if ($priority) fetchpriority="high" @else loading="lazy" @endif decoding="async">
