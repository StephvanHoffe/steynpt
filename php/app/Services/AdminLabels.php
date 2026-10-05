<?php

namespace App\Services;

/** Namen en kleuren in het beheer: coachingstatus en de onderdelen voor trainings- en voedingsschema's. */
final class AdminLabels
{
    public const COACHING_LABEL = [
        'geen' => 'Geen coaching',
        'aangevraagd' => 'Aangevraagd',
        'actief' => 'Actief',
        'gepauzeerd' => 'Gepauzeerd',
        'gestopt' => 'Gestopt',
    ];

    public const COACHING_TONE = [
        'geen' => 'bg-surface text-muted',
        'aangevraagd' => 'bg-accent-tint text-accent',
        'actief' => 'bg-ink text-white',
        'gepauzeerd' => 'bg-surface text-ink',
        'gestopt' => 'bg-surface text-muted line-through decoration-1',
    ];

    /** Eigen onderdeel in het beheer per schematype (gelijk aan PLAN_SECTION in de Next.js-versie). */
    public const PLAN_SECTION = [
        'training' => ['href' => '/admin/trainingsschemas', 'title' => "Trainingsschema's", 'one' => 'trainingsschema'],
        'voeding' => ['href' => '/admin/voedingsschemas', 'title' => "Voedingsschema's", 'one' => 'voedingsschema'],
    ];

    public const PLAN_ICON = ['training' => 'Dumbbell', 'voeding' => 'Salad'];

    public static function coaching(?string $status): string
    {
        return self::COACHING_LABEL[$status] ?? (string) $status;
    }

    public static function planHref(string $type, int $id): string
    {
        return self::PLAN_SECTION[$type]['href'].'/'.$id;
    }

    public static function newPlanHref(string $type, ?string $userId = null): string
    {
        return self::PLAN_SECTION[$type]['href'].'/nieuw'.($userId ? '?lid='.$userId : '');
    }

    /** Enkelvoud of meervoud. */
    public static function plural(int $n, string $one, string $many): string
    {
        return $n === 1 ? $one : $many;
    }
}
