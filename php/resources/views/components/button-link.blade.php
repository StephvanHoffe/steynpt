@props(['href', 'variant' => 'primary', 'size' => null])
<a href="{{ $href }}" {{ $attributes->class(['btn', "btn-{$variant}", 'btn-sm' => $size === 'sm']) }}>{{ $slot }}</a>
