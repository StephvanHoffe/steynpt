<?php

namespace App\View;

use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * Lucide-iconen als inline SVG, met dezelfde opmaak als lucide-react in de Next.js-versie.
 * De vormen staan in resources/icons.php (gegenereerd uit lucide-react).
 */
final class Icon
{
    private static ?array $icons = null;

    public static function svg(string $name, string $class = '', array $attributes = []): HtmlString
    {
        self::$icons ??= require resource_path('icons.php');
        if (! isset(self::$icons[$name])) {
            throw new InvalidArgumentException("Onbekend icoon: {$name}");
        }
        [$classes, $inner] = self::$icons[$name];
        $strokeWidth = $attributes['stroke-width'] ?? '2';
        unset($attributes['stroke-width']);
        // Zonder toegankelijke naam is een icoon versiering.
        if (! isset($attributes['aria-label']) && ! isset($attributes['aria-hidden'])) {
            $attributes['aria-hidden'] = 'true';
        }
        $extra = '';
        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $extra .= ' '.$key.'="'.e($value === true ? $key : $value).'"';
        }
        $class = trim($classes.' '.$class);

        return new HtmlString(
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'.e($strokeWidth).'" stroke-linecap="round" stroke-linejoin="round" class="'.e($class).'"'.$extra.'>'.$inner.'</svg>'
        );
    }
}
