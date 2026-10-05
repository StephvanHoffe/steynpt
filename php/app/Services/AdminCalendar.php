<?php

namespace App\Services;

/**
 * Hulpjes voor de agenda in het beheer: links binnen de agenda, kleur per afspraaktype en korte namen.
 * Gelijk aan src/components/admin/calendar/shared.ts in de Next.js-versie.
 */
final class AdminCalendar
{
    /** Kleur per afspraaktype; overal met het label erbij, zodat kleur nooit de enige aanwijzing is. */
    public const TYPE_COLOR = [
        'personal-training' => '#111315',
        'kennismaking' => '#0b6f78',
        'meting' => '#b45309',
        'online-call' => '#4338ca',
    ];

    public static function typeColor(?string $type): string
    {
        return self::TYPE_COLOR[$type] ?? '#5b6168';
    }

    /**
     * Link binnen de agenda; laat weg wat gelijk is aan de standaard.
     *
     * @param  array{view: string, day: string, cancelled: bool}  $p
     * @param  array{afspraak?: int|null, melding?: string|null}  $extra
     */
    public static function href(array $p, array $extra = []): string
    {
        $q = [];
        if ($p['view'] !== 'week') {
            $q['weergave'] = $p['view'];
        }
        $q['datum'] = $p['day'];
        if ($p['cancelled']) {
            $q['geannuleerd'] = '1';
        }
        if (! empty($extra['afspraak'])) {
            $q['afspraak'] = (string) $extra['afspraak'];
        }
        if (! empty($extra['melding'])) {
            $q['melding'] = $extra['melding'];
        }

        return '/admin/agenda?'.http_build_query($q);
    }

    /** "Mark B." */
    public static function shortName(string $first, string $last): string
    {
        $words = explode(' ', $last);

        return trim($first.' '.mb_substr((string) end($words), 0, 1).'.');
    }

    /** Minuten sinds middernacht als "07:30". */
    public static function hhmm(int|float $minutes): string
    {
        $minutes = (int) $minutes;

        return str_pad((string) intdiv($minutes, 60), 2, '0', STR_PAD_LEFT).':'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    }

    /** "1 afspraak" of "3 afspraken". */
    public static function appointments(int $n): string
    {
        return $n.' '.($n === 1 ? 'afspraak' : 'afspraken');
    }
}
