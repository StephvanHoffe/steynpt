@props(['src', 'alt' => '', 'width', 'height', 'sizes' => '100vw', 'priority' => false])
{{--
  Foto met lichtere WebP-versies (naam.480w.webp enz., gemaakt met deploy/maak-webp.mjs): de browser kiest de kleinste
  die past. Zonder versies gewoon de jpg. De <picture> telt niet mee in de opmaak (contents), de klassen staan op de <img>.
--}}
@php
    $path = ltrim($src, '/');
    $stem = preg_replace('/\.(jpe?g|png)$/i', '', $path);
    $srcset = [];
    foreach (glob(public_path($stem.'.*w.webp')) ?: [] as $file) {
        if (preg_match('/\.(\d+)w\.webp$/', $file, $m)) {
            $srcset[(int) $m[1]] = asset("{$stem}.{$m[1]}w.webp")." {$m[1]}w";
        }
    }
    ksort($srcset);
@endphp
<picture class="contents">
  @if ($srcset)<source type="image/webp" srcset="{{ implode(', ', $srcset) }}" sizes="{{ $sizes }}">@endif
  <img src="{{ asset($path) }}" alt="{{ $alt }}" width="{{ $width }}" height="{{ $height }}" @if ($priority) fetchpriority="high" @else loading="lazy" @endif decoding="async" {{ $attributes }}>
</picture>
