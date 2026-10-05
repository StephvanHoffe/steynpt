<?php

namespace App\View;

use App\Content\Markup;
use Illuminate\Support\HtmlString;

/** Opmaak uit het tekstbeheer omzetten naar HTML, zonder extra witruimte (gelijk aan de React-versie). */
final class Rich
{
    /** Titel: *sterretjes* krijgen de accentkleur (of een andere klasse), Enter wordt een regeleinde. */
    public static function html(string $text, string $accent = 'text-accent'): HtmlString
    {
        $html = '';
        foreach (Markup::parseRich($text) as $part) {
            if (isset($part['br'])) {
                $html .= '<br>';
            } elseif ($part['accent']) {
                $html .= '<span class="'.e($accent).'">'.e($part['text']).'</span>';
            } else {
                $html .= e($part['text']);
            }
        }

        return new HtmlString($html);
    }

    /** Tekst met alinea's: een lege regel begint een nieuwe alinea. */
    public static function paragraphs(string $text): HtmlString
    {
        return new HtmlString(implode('', array_map(fn ($p) => '<p>'.e($p).'</p>', Markup::paragraphs($text))));
    }
}
