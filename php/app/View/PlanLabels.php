<?php

namespace App\View;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/** Namen, statussen en kleuren van schema's, gedeeld door Mijn omgeving en het beheer. */
final class PlanLabels
{
    public const TYPE_LABEL = ['training' => 'Trainingsschema', 'voeding' => 'Voedingsschema'];

    public const STATUS = [
        'genereren' => ['label' => 'AI is bezig', 'tone' => 'bg-surface text-ink'],
        'fout' => ['label' => 'Mislukt', 'tone' => 'bg-danger/10 text-danger'],
        'concept' => ['label' => 'Te controleren', 'tone' => 'bg-accent-tint text-ink'],
        'gepland' => ['label' => 'Ingepland', 'tone' => 'bg-[#eef0ff] text-[#3730a3]'],
        'gepubliceerd' => ['label' => 'Gepubliceerd', 'tone' => 'bg-ink text-white'],
        'vervangen' => ['label' => 'Oude versie', 'tone' => 'bg-surface text-muted'],
    ];

    // Kleur per fase; de tekst van het label maakt het verschil ook zonder kleur duidelijk.
    public const GROUP_TONE = [
        'wacht' => ['badge' => 'bg-danger/10 text-danger', 'dot' => 'bg-danger'],
        'controleren' => ['badge' => 'bg-accent-tint text-accent', 'dot' => 'bg-accent'],
        'binnenkort' => ['badge' => 'bg-[#fdf3e1] text-[#8a4b08]', 'dot' => 'bg-[#c97a12]'],
        'ingepland' => ['badge' => 'bg-[#eef0ff] text-[#3730a3]', 'dot' => 'bg-[#4f46e5]'],
        'actief' => ['badge' => 'bg-success/10 text-success', 'dot' => 'bg-success'],
        'intake' => ['badge' => 'bg-surface text-ink', 'dot' => 'bg-muted'],
        'pauze' => ['badge' => 'bg-surface text-muted', 'dot' => 'bg-line'],
    ];

    private const MONTHS_SHORT = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

    /** Een generatie die na 10 minuten nog loopt is vrijwel zeker afgebroken (bijv. een cronjob die niet draaide). */
    public static function isStuck(string $status, ?CarbonInterface $updatedAt): bool
    {
        return $status === 'genereren' && $updatedAt !== null && $updatedAt->lt(CarbonImmutable::now('UTC')->subMinutes(10));
    }

    /** "14 nov" voor een dag "YYYY-MM-DD". */
    public static function formatPlanDay(string $day): string
    {
        $d = CarbonImmutable::parse("{$day}T12:00:00Z");

        return $d->day.' '.self::MONTHS_SHORT[$d->month - 1];
    }

    /** "vrijdag 14 november" voor een dag "YYYY-MM-DD". */
    public static function formatPlanDayLong(string $day): string
    {
        return Fmt::longDay(CarbonImmutable::parse("{$day}T12:00:00Z"));
    }
}
