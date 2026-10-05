<?php

declare(strict_types=1);

namespace App\Support\Plans;

use App\Support\Js;
use stdClass;

/**
 * Vaste structuur van trainings- en voedingsschema's. Dezelfde schema's worden
 * gebruikt als structured output voor de AI én voor het valideren van Steyns bewerkingen.
 * Alle velden zijn verplicht (eis van structured outputs); lege tekst mag. Er zitten
 * bewust geen lengte- of getalgrenzen in: die controleert de API niet en een te lang
 * AI-antwoord zou dan alsnog afgekeurd worden. De totale omvang begrenst savePlan.
 *
 * De inhoud wordt als JSON opgeslagen en gedeeld met de AI-output en de editor: de sleutels
 * zijn daarom exact dezelfde camelCase-sleutels als in TypeScript.
 *
 * Specificaties: ['string', ?beschrijving], ['number', ?beschrijving],
 * ['array', itemSpec, ?beschrijving], ['object', [sleutel => spec, …], ?beschrijving].
 */
final class PlanSchema
{
    public const EXERCISE = ['object', [
        'name' => ['string', 'Naam van de oefening, in het Nederlands of de gangbare Engelse term'],
        'sets' => ['string', "Aantal sets, bijv. '3' of '3-4'"],
        'reps' => ['string', "Herhalingen of duur, bijv. '8-10', '12 per kant' of '30 sec'"],
        'rest' => ['string', "Rust tussen de sets, bijv. '90 sec'"],
        'notes' => ['string', 'Korte uitvoeringstips of een alternatief bij blessures; leeg als niet nodig'],
    ]];

    public const TRAINING_DAY = ['object', [
        'name' => ['string', "Bijv. 'Dag 1 – Bovenlichaam'"],
        'focus' => ['string'],
        'warmup' => ['string'],
        'exercises' => ['array', self::EXERCISE],
        'cooldown' => ['string'],
    ]];

    public const TRAINING_PLAN = ['object', [
        'title' => ['string'],
        'summary' => ['string', 'Persoonlijke uitleg aan de klant over de opzet van het schema, 2-4 zinnen'],
        'durationWeeks' => ['number', 'Hoeveel weken dit schema gevolgd wordt voor een evaluatie'],
        'daysPerWeek' => ['number'],
        'days' => ['array', self::TRAINING_DAY],
        'progression' => ['string', 'Hoe de klant week op week progressie maakt'],
        'tips' => ['array', ['string']],
    ]];

    public const MEAL_OPTION = ['object', [
        'title' => ['string'],
        'ingredients' => ['string', 'Ingrediënten met hoeveelheden'],
        'kcal' => ['number'],
        'protein' => ['number', 'Gram eiwit'],
    ]];

    public const MEAL = ['object', [
        'name' => ['string', "Bijv. 'Ontbijt', 'Lunch', 'Tussendoor'"],
        'time' => ['string', "Richttijd, bijv. '07:30' of 'Na de training'"],
        'options' => ['array', self::MEAL_OPTION],
    ]];

    public const NUTRITION_PLAN = ['object', [
        'title' => ['string'],
        'summary' => ['string', 'Persoonlijke uitleg aan de klant over de aanpak, 2-4 zinnen'],
        'targets' => ['object', [
            'calories' => ['number'],
            'protein' => ['number'],
            'carbs' => ['number'],
            'fat' => ['number'],
            'water' => ['string', "Bijv. '2-2,5 liter per dag'"],
        ]],
        'meals' => ['array', self::MEAL],
        'avoid' => ['array', ['string'], 'Wat de klant moet vermijden, o.a. op basis van allergieën en eetstijl'],
        'tips' => ['array', ['string']],
    ]];

    /** Specificatie per schematype (planSchemaFor). */
    public static function planSchemaFor(string $type): array
    {
        return $type === 'training' ? self::TRAINING_PLAN : self::NUTRITION_PLAN;
    }

    /**
     * Valideert de inhoud van een schema (zoals safeParse): onbekende sleutels vallen weg, de volgorde van
     * de sleutels is die van het schema. Geeft [data, fouten]; fouten per pad ("days.0.exercises.1.name",
     * "" voor de hele inhoud) met de standaardmeldingen van zod.
     *
     * @return array{0: ?array<string, mixed>, 1: array<string, string>}
     */
    public static function parse(string $type, mixed $content): array
    {
        return self::validate(self::planSchemaFor($type), $content);
    }

    /** @return array{0: ?array<string, mixed>, 1: array<string, string>} */
    public static function parseTrainingPlan(mixed $content): array
    {
        return self::validate(self::TRAINING_PLAN, $content);
    }

    /** @return array{0: ?array<string, mixed>, 1: array<string, string>} */
    public static function parseNutritionPlan(mixed $content): array
    {
        return self::validate(self::NUTRITION_PLAN, $content);
    }

    /** @return array{0: mixed, 1: array<string, string>} */
    public static function validate(array $spec, mixed $value): array
    {
        $errors = [];
        $data = self::check($spec, $value, true, '', $errors);

        return $errors === [] ? [$data, []] : [null, $errors];
    }

    private static function check(array $spec, mixed $value, bool $present, string $path, array &$errors): mixed
    {
        $fail = function (string $expected) use ($value, $present, $path, &$errors): void {
            $errors[$path] ??= "Invalid input: expected {$expected}, received ".Js::zodType($value, $present);
        };
        $child = fn (string|int $key) => $path === '' ? (string) $key : "{$path}.{$key}";

        switch ($spec[0]) {
            case 'string':
                if (! $present || ! is_string($value)) {
                    $fail('string');

                    return null;
                }

                return $value;

            case 'number':
                if (! $present || ! (is_int($value) || is_float($value)) || ! is_finite((float) $value)) {
                    $fail('number');

                    return null;
                }

                return $value;

            case 'array':
                if (! $present || ! is_array($value) || ! array_is_list($value)) {
                    $fail('array');

                    return null;
                }
                $out = [];
                foreach ($value as $i => $item) {
                    $out[] = self::check($spec[1], $item, true, $child($i), $errors);
                }

                return $out;

            case 'object':
                if ($value instanceof stdClass) {
                    $value = get_object_vars($value);
                }
                if (! $present || ! is_array($value) || ($value !== [] && array_is_list($value))) {
                    $fail('object');

                    return null;
                }
                $out = [];
                foreach ($spec[1] as $key => $field) {
                    $out[$key] = self::check($field, $value[$key] ?? null, array_key_exists($key, $value), $child($key), $errors);
                }

                return $out;
        }

        return null;
    }

    /**
     * JSON Schema voor structured output van de AI, met dezelfde beschrijvingen als de zod-schema's
     * (alle velden verplicht, geen extra velden).
     */
    public static function jsonSchema(string|array $typeOrSpec): array
    {
        $spec = is_string($typeOrSpec) ? self::planSchemaFor($typeOrSpec) : $typeOrSpec;
        $schema = match ($spec[0]) {
            'string' => ['type' => 'string'],
            'number' => ['type' => 'number'],
            'array' => ['type' => 'array', 'items' => self::jsonSchema($spec[1])],
            'object' => [
                'type' => 'object',
                'properties' => array_map(fn (array $field) => self::jsonSchema($field), $spec[1]),
                'additionalProperties' => false,
                'required' => array_keys($spec[1]),
            ],
        };
        $description = $spec[0] === 'array' || $spec[0] === 'object' ? ($spec[2] ?? null) : ($spec[1] ?? null);
        if ($description !== null) {
            $schema['description'] = $description;
        }

        return $schema;
    }

    // ---------------------------------------------------------------------------
    // Lege onderdelen voor de editor

    /** @return array{name: string, sets: string, reps: string, rest: string, notes: string} */
    public static function emptyExercise(): array
    {
        return ['name' => '', 'sets' => '3', 'reps' => '10', 'rest' => '90 sec', 'notes' => ''];
    }

    public static function emptyTrainingDay(int $n): array
    {
        return [
            'name' => "Dag {$n}",
            'focus' => '',
            'warmup' => '',
            'exercises' => [self::emptyExercise()],
            'cooldown' => '',
        ];
    }

    /** @return array{title: string, ingredients: string, kcal: int, protein: int} */
    public static function emptyMealOption(): array
    {
        return ['title' => '', 'ingredients' => '', 'kcal' => 0, 'protein' => 0];
    }

    public static function emptyMeal(): array
    {
        return ['name' => '', 'time' => '', 'options' => [self::emptyMealOption()]];
    }

    public static function emptyTrainingPlan(int $daysPerWeek = 3): array
    {
        return [
            'title' => 'Trainingsschema',
            'summary' => '',
            'durationWeeks' => 6,
            'daysPerWeek' => $daysPerWeek,
            'days' => array_map(fn (int $i) => self::emptyTrainingDay($i + 1), $daysPerWeek > 0 ? range(0, $daysPerWeek - 1) : []),
            'progression' => '',
            'tips' => [],
        ];
    }

    /** @param  array{calories: int|float, protein: int|float, carbs: int|float, fat: int|float}|null  $targets  bijv. Intake::estimateTargets() */
    public static function emptyNutritionPlan(?array $targets = null): array
    {
        return [
            'title' => 'Voedingsschema',
            'summary' => '',
            'targets' => array_merge(['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0, 'water' => '2-2,5 liter per dag'], $targets ?? []),
            'meals' => array_map(fn (string $name) => [...self::emptyMeal(), 'name' => $name], ['Ontbijt', 'Lunch', 'Avondeten']),
            'avoid' => [],
            'tips' => [],
        ];
    }
}
