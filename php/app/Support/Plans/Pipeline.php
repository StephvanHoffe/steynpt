<?php

declare(strict_types=1);

namespace App\Support\Plans;

use App\Support\Agenda;
use App\Support\Js;
use Collator;

/**
 * Schema-planning: in welke fase zit een klant per schematype, en wanneer is hij toe aan een nieuw schema?
 * Puur (geen database), zodat het los te testen is. Dagen zijn "YYYY-MM-DD" in Europe/Amsterdam.
 *
 * PipelineInput is een array met de sleutels:
 *  - type: "training"|"voeding"
 *  - coachingStatus: "geen"|"aangevraagd"|"actief"|"gepauzeerd"|"gestopt"
 *  - wants: gewenste schema's uit de intake (list<string>); null als er nog geen intake is
 *  - current: het schema dat de klant nu ziet: {publishedDay, renewOn (?string), durationWeeks (?number)} of null
 *  - open: concept dat (nog) niet gepubliceerd is: {status: "genereren"|"concept"|"fout", stuck: bool} of null
 *  - scheduled (optioneel): goedgekeurd schema dat op een latere dag ingaat: {startsOn} of null
 */
final class Pipeline
{
    /** Binnen zoveel dagen telt een nieuw schema als "komende week". */
    public const SOON_DAYS = 7;

    private const DEFAULT_RENEW_WEEKS = ['training' => 6, 'voeding' => 4];

    public const STAGE_GROUPS = [
        ['id' => 'wacht', 'label' => 'Wacht op nieuw schema', 'hint' => 'Jij maakt het schema'],
        ['id' => 'controleren', 'label' => 'Te controleren', 'hint' => 'Concept staat klaar'],
        ['id' => 'binnenkort', 'label' => 'Komende week', 'hint' => 'Nieuw schema binnen '.self::SOON_DAYS.' dagen'],
        ['id' => 'ingepland', 'label' => 'Ingepland', 'hint' => 'Nieuw schema start later'],
        ['id' => 'actief', 'label' => 'Actief schema', 'hint' => 'Loopt nog langer dan een week'],
        ['id' => 'intake', 'label' => 'Wacht op intake', 'hint' => 'De klant is aan zet'],
        ['id' => 'pauze', 'label' => 'Gepauzeerd', 'hint' => 'Coaching staat stil'],
    ];

    public const STAGES = [
        'eerste' => ['group' => 'wacht', 'label' => 'Eerste schema nodig'],
        'verlopen' => ['group' => 'wacht', 'label' => 'Toe aan nieuw schema'],
        'bezig' => ['group' => 'controleren', 'label' => 'AI is bezig'],
        'mislukt' => ['group' => 'controleren', 'label' => 'Concept mislukt'],
        'controleren' => ['group' => 'controleren', 'label' => 'Te controleren'],
        'binnenkort' => ['group' => 'binnenkort', 'label' => 'Komende week'],
        'gepland' => ['group' => 'ingepland', 'label' => 'Ingepland'],
        'actief' => ['group' => 'actief', 'label' => 'Actief'],
        'intake' => ['group' => 'intake', 'label' => 'Wacht op intake'],
        'pauze' => ['group' => 'pauze', 'label' => 'Gepauzeerd'],
    ];

    /** Looptijd van een schema: een trainingsschema volgens de duur in het schema, voeding 4 weken. */
    public static function defaultRenewWeeks(string $type, int|float|null $durationWeeks = null): int
    {
        if ($type === 'training' && Js::truthy($durationWeeks) && $durationWeeks >= 1 && $durationWeeks <= 26) {
            return (int) Js::round($durationWeeks);
        }

        return self::DEFAULT_RENEW_WEEKS[$type];
    }

    public static function defaultRenewOn(string $type, string $fromDay, int|float|null $durationWeeks = null): string
    {
        return Agenda::addDays($fromDay, self::defaultRenewWeeks($type, $durationWeeks) * 7);
    }

    /** Hoort deze klant in het overzicht van dit schematype? */
    public static function inPipeline(array $input): bool
    {
        if (($input['current'] ?? null) !== null || ($input['open'] ?? null) !== null || ($input['scheduled'] ?? null) !== null) {
            return true;
        }
        $status = $input['coachingStatus'];
        if ($status !== 'aangevraagd' && $status !== 'actief' && $status !== 'gepauzeerd') {
            return false;
        }

        return $input['wants'] === null || in_array($input['type'], $input['wants'], true);
    }

    /**
     * Dag waarop het huidige schema vernieuwd moet worden.
     *
     * @param  array{publishedDay: string, renewOn: ?string, durationWeeks: int|float|null}  $current
     */
    public static function dueOn(string $type, array $current): string
    {
        return $current['renewOn'] ?? self::defaultRenewOn($type, $current['publishedDay'], $current['durationWeeks'] ?? null);
    }

    /** @return array{stage: string, dueOn: ?string} */
    public static function planStage(array $input, string $today): array
    {
        $current = $input['current'] ?? null;
        $due = $current !== null ? self::dueOn($input['type'], $current) : null;
        $open = $input['open'] ?? null;
        if ($open !== null) {
            $stage = $open['status'] === 'fout' || Js::truthy($open['stuck'] ?? null) ? 'mislukt' : ($open['status'] === 'genereren' ? 'bezig' : 'controleren');

            return ['stage' => $stage, 'dueOn' => $due];
        }
        // Het volgende schema is al goedgekeurd: de "nieuw schema"-datum is dan de startdatum daarvan.
        $scheduled = $input['scheduled'] ?? null;
        if ($scheduled !== null) {
            return ['stage' => 'gepland', 'dueOn' => $scheduled['startsOn']];
        }
        if ($input['coachingStatus'] === 'gepauzeerd' || $input['coachingStatus'] === 'gestopt') {
            return ['stage' => 'pauze', 'dueOn' => $due];
        }
        if ($due === null || $due === '') {
            return ['stage' => $input['wants'] === null ? 'intake' : 'eerste', 'dueOn' => null];
        }
        if (strcmp($due, $today) <= 0) {
            return ['stage' => 'verlopen', 'dueOn' => $due];
        }
        if (strcmp($due, Agenda::addDays($today, self::SOON_DAYS)) <= 0) {
            return ['stage' => 'binnenkort', 'dueOn' => $due];
        }

        return ['stage' => 'actief', 'dueOn' => $due];
    }

    private static function groupOrder(string $group): int
    {
        return (int) array_search($group, array_column(self::STAGE_GROUPS, 'id'), true);
    }

    /**
     * Meest dringende eerst: per groep, daarna de vroegste datum. Te gebruiken met usort().
     *
     * @param  array{stage: string, dueOn: ?string, name: string}  $a
     * @param  array{stage: string, dueOn: ?string, name: string}  $b
     */
    public static function compareByUrgency(array $a, array $b): int
    {
        return (self::groupOrder(self::STAGES[$a['stage']]['group']) - self::groupOrder(self::STAGES[$b['stage']]['group']))
            ?: (self::localeCompare($a['dueOn'] ?? '9999', $b['dueOn'] ?? '9999')
            ?: self::localeCompare($a['name'], $b['name']));
    }

    /** String.prototype.localeCompare(…, "nl"). */
    private static function localeCompare(string $a, string $b): int
    {
        static $collator = null;
        if (class_exists(Collator::class)) {
            $collator ??= new Collator('nl');
            $result = $collator->compare($a, $b);
            if ($result !== false) {
                return $result;
            }
        }

        return strcmp($a, $b) <=> 0;
    }

    /**
     * @param  iterable<array{stage: string}>  $rows
     * @return array<string, int> aantal per groep, in de volgorde van STAGE_GROUPS
     */
    public static function countGroups(iterable $rows): array
    {
        $counts = array_fill_keys(array_column(self::STAGE_GROUPS, 'id'), 0);
        foreach ($rows as $r) {
            $counts[self::STAGES[$r['stage']]['group']]++;
        }

        return $counts;
    }

    /** Aantal dagen van `from` tot `to` (NAN bij een ongeldige dag, zoals in JavaScript). */
    public static function daysBetween(string $from, string $to): int|float
    {
        $f = Js::parseDay($from);
        $t = Js::parseDay($to);
        if ($f === null || $t === null) {
            return NAN;
        }

        return (int) Js::round(($t - $f) / 864e5);
    }

    /** "vandaag", "over 5 dagen", "3 weken geleden" … */
    public static function relativeDay(string $today, string $day): string
    {
        $n = self::daysBetween($today, $day);
        if ($n === 0) {
            return 'vandaag';
        }
        if ($n === 1) {
            return 'morgen';
        }
        if ($n === -1) {
            return 'gisteren';
        }
        $abs = abs($n);
        $amount = $abs < 14 ? Js::numberToString($abs).' dagen' : Js::numberToString(Js::num(Js::round($abs / 7))).' weken';

        return $n > 0 ? "over {$amount}" : "{$amount} geleden";
    }
}
