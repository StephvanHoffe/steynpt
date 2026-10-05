@props(['variant' => 'dark', 'class' => 'h-10 w-auto'])
<img src="{{ asset($variant === 'light' ? 'brand/steynpt-mark-white.png' : 'brand/steynpt-mark-black.png') }}" alt="" width="112" height="215" class="{{ $class }}" loading="lazy" decoding="async">
