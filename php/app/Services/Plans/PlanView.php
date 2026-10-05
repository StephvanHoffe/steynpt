<?php

namespace App\Services\Plans;

use App\Support\Agenda;
use App\Support\Plans\Allergens;
use App\View\Fmt;
use App\View\PlanLabels;
use DateTimeInterface;
use ReflectionClass;

/** Kleine hulpfuncties voor de schemapagina's in het beheer en Mijn omgeving. */
final class PlanView
{
    /** "5 okt" (Nederlandse tijd). */
    public static function shortDate(DateTimeInterface $date): string
    {
        return PlanLabels::formatPlanDay(Agenda::zonedParts(Fmt::local($date))['day']);
    }

    /** "5 okt 2026, 14:03" (Nederlandse tijd), zoals Intl.DateTimeFormat("nl-NL") met datum en tijd. */
    public static function dateTime(DateTimeInterface $date): string
    {
        $local = Fmt::local($date);

        return self::shortDate($date).' '.$local->year.', '.$local->format('H:i');
    }

    /** Zelfde inhoud? (getallen als 450 en 450.0 tellen als gelijk, net als in JSON) */
    public static function sameContent(mixed $a, mixed $b): bool
    {
        return json_encode(self::normalizeNumbers($a)) === json_encode(self::normalizeNumbers($b));
    }

    private static function normalizeNumbers(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(self::normalizeNumbers(...), $value);
        }

        return is_float($value) && is_finite($value) && floor($value) === $value && abs($value) < PHP_INT_MAX ? (int) $value : $value;
    }

    /**
     * Gegevens voor de allergenencontrole in de editor (in de browser, bij elke wijziging opnieuw):
     * dezelfde regels en woordenlijsten als App\Support\Plans\Allergens.
     *
     * @param  array{allergies: list<string>, diet: string}  $intake
     */
    public static function allergenCheck(array $intake): array
    {
        $rules = [];
        foreach ($intake['allergies'] as $a) {
            if (isset(Allergens::ALLERGY_RULES[$a])) {
                $rules[] = ['rule' => Allergens::ALLERGY_RULES[$a], 'reason' => 'allergie: '.Allergens::ALLERGY_RULES[$a]['label'], 'diet' => false];
            }
        }
        if (isset(Allergens::DIET_RULES[$intake['diet']])) {
            $rule = Allergens::DIET_RULES[$intake['diet']];
            $rules[] = ['rule' => $rule, 'reason' => 'eetstijl: '.$rule['label'], 'diet' => true];
        }
        $lists = new ReflectionClass(Allergens::class);

        return [
            'rules' => $rules,
            'notAMatch' => $lists->getConstant('NOT_A_MATCH'),
            'absent' => $lists->getConstant('ABSENT'),
            'substitute' => $lists->getConstant('SUBSTITUTE'),
        ];
    }
}
