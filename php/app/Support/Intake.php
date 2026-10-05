<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Intakevragen voor het maken van een trainings- en/of voedingsschema.
 * Gedeeld door het formulier, de server-actie, de AI-prompt en het beheerscherm.
 *
 * Een gevalideerde intake (IntakeData) is een array met dezelfde camelCase-sleutels als in TypeScript;
 * optionele teksten en targetWeightKg ontbreken als ze leeg zijn (in JSON: undefined).
 */
final class Intake
{
    /** Doelen (uit src/lib/site.ts: GOALS). */
    public const GOALS = [
        ['id' => 'afvallen', 'label' => 'Afvallen'],
        ['id' => 'spieropbouw', 'label' => 'Spiermassa opbouwen'],
        ['id' => 'fitter', 'label' => 'Fitter en meer energie'],
        ['id' => 'leefstijl', 'label' => 'Gezondere leefstijl'],
        ['id' => 'prestatie', 'label' => 'Sportprestatie / topsport'],
        ['id' => 'herstel', 'label' => 'Sterker na blessure of klachten'],
    ];

    public const SEXES = [
        ['id' => 'man', 'label' => 'Man'],
        ['id' => 'vrouw', 'label' => 'Vrouw'],
        ['id' => 'anders', 'label' => 'Anders / zeg ik liever niet'],
    ];

    public const EXPERIENCE = [
        ['id' => 'beginner', 'label' => 'Beginner', 'hint' => 'Minder dan een half jaar structureel trainen'],
        ['id' => 'gemiddeld', 'label' => 'Gemiddeld', 'hint' => 'Een half jaar tot twee jaar'],
        ['id' => 'gevorderd', 'label' => 'Gevorderd', 'hint' => 'Meer dan twee jaar; je kent de basisoefeningen goed'],
    ];

    public const SESSION_MINUTES = [30, 45, 60, 75, 90];

    public const LOCATIONS = [
        ['id' => 'sportschool', 'label' => 'Sportschool'],
        ['id' => 'thuis-materiaal', 'label' => 'Thuis, met wat materiaal'],
        ['id' => 'thuis-geen', 'label' => 'Thuis, zonder materiaal'],
        ['id' => 'buiten', 'label' => 'Buiten'],
    ];

    public const ACTIVITY_LEVELS = [
        ['id' => 'zittend', 'label' => 'Vooral zittend', 'hint' => 'Kantoorwerk, weinig wandelen', 'factor' => 1.2],
        ['id' => 'licht', 'label' => 'Licht actief', 'hint' => 'Dagelijks wat wandelen of fietsen', 'factor' => 1.375],
        ['id' => 'actief', 'label' => 'Actief', 'hint' => 'Veel op de been of fysiek werk', 'factor' => 1.55],
        ['id' => 'zeer-actief', 'label' => 'Zeer actief', 'hint' => 'Zwaar fysiek werk', 'factor' => 1.725],
    ];

    public const DIETS = [
        ['id' => 'alles', 'label' => 'Ik eet alles'],
        ['id' => 'flexitarisch', 'label' => 'Flexitarisch'],
        ['id' => 'pescotarisch', 'label' => 'Pescotarisch (wel vis, geen vlees)'],
        ['id' => 'vegetarisch', 'label' => 'Vegetarisch'],
        ['id' => 'veganistisch', 'label' => 'Veganistisch'],
        ['id' => 'halal', 'label' => 'Halal'],
    ];

    /** De 14 wettelijke allergenen (EU) plus lactose-intolerantie. */
    public const ALLERGIES = [
        ['id' => 'gluten', 'label' => 'Gluten'],
        ['id' => 'melk', 'label' => 'Melk (koemelkeiwit)'],
        ['id' => 'lactose', 'label' => 'Lactose'],
        ['id' => 'ei', 'label' => 'Ei'],
        ['id' => 'pinda', 'label' => 'Pinda'],
        ['id' => 'noten', 'label' => 'Noten'],
        ['id' => 'soja', 'label' => 'Soja'],
        ['id' => 'vis', 'label' => 'Vis'],
        ['id' => 'schaaldieren', 'label' => 'Schaaldieren'],
        ['id' => 'weekdieren', 'label' => 'Weekdieren'],
        ['id' => 'selderij', 'label' => 'Selderij'],
        ['id' => 'mosterd', 'label' => 'Mosterd'],
        ['id' => 'sesam', 'label' => 'Sesam'],
        ['id' => 'lupine', 'label' => 'Lupine'],
        ['id' => 'sulfiet', 'label' => 'Sulfiet'],
    ];

    public const PLAN_WANTS = [
        ['id' => 'training', 'label' => 'Trainingsschema'],
        ['id' => 'voeding', 'label' => 'Voedingsschema'],
    ];

    /** Optionele tekstvelden met hun maximale lengte. */
    private const OPTIONAL_TEXTS = [
        'goalDetails' => 600,
        'equipment' => 400,
        'sport' => 200,
        'injuries' => 800,
        'allergiesOther' => 400,
        'dislikes' => 400,
        'medical' => 800,
    ];

    private const MULTI_FIELDS = ['wants', 'allergies'];

    /** @return list<string> */
    private static function ids(array $list): array
    {
        return array_column($list, 'id');
    }

    /**
     * Valideert en normaliseert een intake (intakeSchema). Zelfde regels, standaardwaarden en meldingen.
     * Geeft [data, fouten]: data is null bij fouten; fouten bevatten per veld (bovenste niveau, zoals
     * fieldErrorsFrom) de eerste melding.
     *
     * @param  int|null  $thisYear  huidig jaar voor de leeftijdsgrenzen (standaard: nu, Nederlandse tijd)
     * @return array{0: ?array<string, mixed>, 1: array<string, string>}
     */
    public static function validate(array $input, ?int $thisYear = null): array
    {
        $thisYear ??= CarbonImmutable::now(Agenda::TIME_ZONE)->year;
        $errors = [];
        $data = [];
        $has = fn (string $key) => array_key_exists($key, $input);
        $fail = function (string $key, string $message) use (&$errors): void {
            $errors[$key] ??= $message;
        };

        // wants: z.array(z.enum(PLAN_WANTS)).min(1, …)
        $wants = self::enumArray($input, 'wants', self::ids(self::PLAN_WANTS), $fail);
        if ($wants !== null && count($wants) < 1) {
            $fail('wants', 'Kies minimaal één schema');
        }
        $data['wants'] = $wants;

        $data['goal'] = self::enum($input, 'goal', self::ids(self::GOALS), 'Kies je belangrijkste doel', $fail);
        self::optionalText($input, 'goalDetails', $data, $fail);
        $data['sex'] = self::enum($input, 'sex', self::ids(self::SEXES), 'Maak een keuze', $fail);

        $data['birthYear'] = self::number($input, 'birthYear', $fail, 'Vul je geboortejaar in', 'Vul je geboortejaar in', [
            [fn ($n) => $n >= $thisYear - 90, 'Vul een geldig geboortejaar in'],
            [fn ($n) => $n <= $thisYear - 14, 'Je moet minimaal 14 jaar zijn'],
        ]);
        $data['heightCm'] = self::number($input, 'heightCm', $fail, 'Vul je lengte in', 'Vul je lengte in hele centimeters in', [
            [fn ($n) => $n >= 120, 'Vul je lengte in centimeters in'],
            [fn ($n) => $n <= 230, 'Vul je lengte in centimeters in'],
        ]);
        // weightKg: komma als decimaalteken
        $data['weightKg'] = self::number(self::decimalInput($input, 'weightKg'), 'weightKg', $fail, 'Vul je gewicht in', null, [
            [fn ($n) => $n >= 35, 'Vul een geldig gewicht in'],
            [fn ($n) => $n <= 300, 'Vul een geldig gewicht in'],
        ]);
        // targetWeightKg: optioneel; "" telt als niet ingevuld
        if ($has('targetWeightKg') && $input['targetWeightKg'] !== '') {
            $data['targetWeightKg'] = self::number(self::decimalInput($input, 'targetWeightKg'), 'targetWeightKg', $fail, 'Vul een geldig streefgewicht in', null, [
                [fn ($n) => $n >= 35, 'Vul een geldig streefgewicht in'],
                [fn ($n) => $n <= 300, 'Vul een geldig streefgewicht in'],
            ]);
        }

        $data['experience'] = self::enum($input, 'experience', self::ids(self::EXPERIENCE), 'Kies je ervaring', $fail);
        $data['trainingDays'] = self::number($input, 'trainingDays', $fail, 'Kies hoe vaak je per week wilt trainen', 'Kies hoe vaak je per week wilt trainen', [
            [fn ($n) => $n >= 1, 'Kies hoe vaak je per week wilt trainen'],
            [fn ($n) => $n <= 7, 'Kies hoe vaak je per week wilt trainen'],
        ]);
        $data['sessionMinutes'] = self::number($input, 'sessionMinutes', $fail, 'Kies hoe lang een training mag duren', null, [
            [fn ($n) => in_array($n, self::SESSION_MINUTES, false), 'Kies hoe lang een training mag duren'],
        ]);
        $data['location'] = self::enum($input, 'location', self::ids(self::LOCATIONS), 'Kies waar je traint', $fail);
        self::optionalText($input, 'equipment', $data, $fail);
        self::optionalText($input, 'sport', $data, $fail);
        self::optionalText($input, 'injuries', $data, $fail);
        $data['activityLevel'] = self::enum($input, 'activityLevel', self::ids(self::ACTIVITY_LEVELS), 'Kies hoe actief je dagelijks bent', $fail);
        $data['diet'] = self::enum($input, 'diet', self::ids(self::DIETS), 'Kies je eetstijl', $fail);
        // allergies: standaard een lege lijst
        $data['allergies'] = $has('allergies') ? self::enumArray($input, 'allergies', self::ids(self::ALLERGIES), $fail) : [];
        self::optionalText($input, 'allergiesOther', $data, $fail);
        self::optionalText($input, 'dislikes', $data, $fail);
        $data['mealsPerDay'] = self::number($input, 'mealsPerDay', $fail, 'Kies het aantal eetmomenten', 'Kies het aantal eetmomenten', [
            [fn ($n) => $n >= 2, 'Kies het aantal eetmomenten'],
            [fn ($n) => $n <= 6, 'Kies het aantal eetmomenten'],
        ]);
        self::optionalText($input, 'medical', $data, $fail);

        return $errors === [] ? [$data, []] : [null, $errors];
    }

    /** z.enum(ids, message): alleen een van de ids is goed. */
    private static function enum(array $input, string $key, array $ids, string $message, callable $fail): ?string
    {
        $value = $input[$key] ?? null;
        if (! is_string($value) || ! in_array($value, $ids, true)) {
            $fail($key, $message);

            return null;
        }

        return $value;
    }

    /** z.array(z.enum(ids)) met de standaardmeldingen van zod. */
    private static function enumArray(array $input, string $key, array $ids, callable $fail): ?array
    {
        $value = $input[$key] ?? null;
        if (! is_array($value) || ($value !== [] && ! array_is_list($value))) {
            $fail($key, 'Maak een geldige keuze');

            return null;
        }
        $message = 'Maak een geldige keuze';
        $ok = true;
        foreach ($value as $item) {
            if (! is_string($item) || ! in_array($item, $ids, true)) {
                $fail($key, $message);
                $ok = false;
            }
        }

        return $ok ? $value : null;
    }

    /**
     * z.string().trim().max(max, "Maximaal … tekens").optional().transform((v) => v || undefined):
     * een lege tekst laat de sleutel weg.
     */
    private static function optionalText(array $input, string $key, array &$data, callable $fail): void
    {
        if (! array_key_exists($key, $input)) {
            return;
        }
        $value = $input[$key];
        if (! is_string($value)) {
            $fail($key, 'Controleer dit veld');

            return;
        }
        $max = self::OPTIONAL_TEXTS[$key];
        $text = Js::trim($value);
        if (mb_strlen($text, 'UTF-8') > $max) {
            $fail($key, "Maximaal {$max} tekens");

            return;
        }
        if ($text !== '') {
            $data[$key] = $text;
        }
    }

    /** Voorbewerking van decimal(): tekst krijgt een punt in plaats van de eerste komma en wordt getrimd. */
    private static function decimalInput(array $input, string $key): array
    {
        if (isset($input[$key]) && is_string($input[$key])) {
            $input[$key] = Js::trim(preg_replace('/,/', '.', $input[$key], 1) ?? $input[$key]);
        }

        return $input;
    }

    /**
     * z.coerce.number({ error: typeMessage }).int(intMessage).min(…).max(…): Number()-coercion, daarna de
     * controles in volgorde; de eerste fout telt.
     *
     * @param  list<array{0: callable(float): bool, 1: string}>  $checks
     */
    private static function number(array $input, string $key, callable $fail, string $typeMessage, ?string $intMessage, array $checks): int|float|null
    {
        $n = array_key_exists($key, $input) ? Js::toNumber($input[$key]) : NAN;
        if (! is_finite($n)) {
            $fail($key, $typeMessage);

            return null;
        }
        if ($intMessage !== null && ! Js::isSafeInteger($n)) {
            $fail($key, $intMessage);

            return null;
        }
        foreach ($checks as [$check, $message]) {
            if (! $check($n)) {
                $fail($key, $message);

                return null;
            }
        }

        return Js::num($n);
    }

    /**
     * Zet formulierinvoer om naar een array die validate() kan valideren (incl. meerkeuzevelden).
     * Laravel maakt van lege velden null (ConvertEmptyStringsToNull); een formulier stuurt altijd tekst,
     * dus null wordt hier weer "".
     */
    public static function intakeFromFormData(array $formData): array
    {
        $all = function (string $key) use ($formData): array {
            $value = $formData[$key] ?? [];
            $values = is_array($value) ? array_values($value) : [$value];

            return array_values(array_map(fn ($v) => $v ?? '', array_filter($values, fn ($v) => is_string($v) || $v === null)));
        };
        $raw = ['wants' => $all('wants'), 'allergies' => $all('allergies')];
        foreach ($formData as $key => $value) {
            $key = (string) $key;
            if ((is_string($value) || $value === null) && ! in_array($key, self::MULTI_FIELDS, true) && ! str_starts_with($key, '$') && $key !== 'consent') {
                $raw[$key] = $value ?? '';
            }
        }

        return $raw;
    }

    /** @param  list<array{id: string, label: string}>  $list */
    public static function labelOf(array $list, ?string $id): string
    {
        foreach ($list as $item) {
            if ($item['id'] === $id) {
                return $item['label'];
            }
        }

        return $id ?? '';
    }

    /**
     * Richtwaarden voor energie en macro's (Mifflin-St Jeor + activiteitsfactor).
     * Dit is een startpunt voor de AI en voor Steyn, geen medisch advies.
     *
     * @param  array<string, mixed>  $intake  gevalideerde intake
     * @return array{bmr: int, maintenance: int, calories: int, protein: int, carbs: int, fat: int}
     */
    public static function estimateTargets(array $intake, ?int $year = null): array
    {
        $year ??= CarbonImmutable::now(Agenda::TIME_ZONE)->year;
        $age = $year - $intake['birthYear'];
        $sexOffset = $intake['sex'] === 'man' ? 5 : ($intake['sex'] === 'vrouw' ? -161 : -78);
        $bmr = 10 * $intake['weightKg'] + 6.25 * $intake['heightCm'] - 5 * $age + $sexOffset;
        $activity = 1.375;
        foreach (self::ACTIVITY_LEVELS as $level) {
            if ($level['id'] === $intake['activityLevel']) {
                $activity = $level['factor'];
                break;
            }
        }
        $factor = min(1.9, $activity + $intake['trainingDays'] * 0.03);
        $maintenance = $bmr * $factor;

        $goal = $intake['goal'];
        $adjustment = $goal === 'afvallen' ? 0.85 : ($goal === 'spieropbouw' ? 1.1 : 1);
        $floor = max($bmr, $intake['sex'] === 'man' ? 1500 : 1200);
        $calories = max($maintenance * $adjustment, $floor);

        $proteinPerKg = $goal === 'afvallen' || $goal === 'spieropbouw' || $goal === 'prestatie' ? 1.8 : 1.5;
        $protein = min($intake['weightKg'] * $proteinPerKg, 220);
        $fat = ($calories * 0.28) / 9;
        $carbs = max(0, ($calories - $protein * 4 - $fat * 9) / 4);

        $round = fn (int|float $n, int $step) => Js::num(Js::round($n / $step) * $step);

        return [
            'bmr' => $round($bmr, 10),
            'maintenance' => $round($maintenance, 10),
            'calories' => $round($calories, 10),
            'protein' => $round($protein, 5),
            'carbs' => $round($carbs, 5),
            'fat' => $round($fat, 5),
        ];
    }

    /**
     * Leesbare samenvatting van de intake (voor de AI-prompt en het beheerscherm). Bevat geen naam of contactgegevens.
     *
     * @param  array<string, mixed>  $intake  gevalideerde intake
     * @return list<array{section: 'algemeen'|'training'|'voeding', label: string, value: string}>
     */
    public static function intakeSummary(array $intake, ?int $year = null): array
    {
        $year ??= CarbonImmutable::now(Agenda::TIME_ZONE)->year;
        $n = fn (int|float $v) => Js::numberToString($v);
        $allergies = array_map(fn (string $a) => self::labelOf(self::ALLERGIES, $a), $intake['allergies'] ?? []);
        $allergies[] = $intake['allergiesOther'] ?? null;

        $rows = [
            ['section' => 'algemeen', 'label' => "Gewenste schema's", 'value' => implode(', ', array_map(fn (string $w) => self::labelOf(self::PLAN_WANTS, $w), $intake['wants']))],
            ['section' => 'algemeen', 'label' => 'Doel', 'value' => self::labelOf(self::GOALS, $intake['goal'])],
            ['section' => 'algemeen', 'label' => 'Toelichting doel', 'value' => $intake['goalDetails'] ?? ''],
            ['section' => 'algemeen', 'label' => 'Geslacht', 'value' => self::labelOf(self::SEXES, $intake['sex'])],
            ['section' => 'algemeen', 'label' => 'Leeftijd', 'value' => $n($year - $intake['birthYear']).' jaar'],
            ['section' => 'algemeen', 'label' => 'Lengte', 'value' => $n($intake['heightCm']).' cm'],
            ['section' => 'algemeen', 'label' => 'Gewicht', 'value' => $n($intake['weightKg']).' kg'],
            ['section' => 'algemeen', 'label' => 'Streefgewicht', 'value' => Js::truthy($intake['targetWeightKg'] ?? null) ? $n($intake['targetWeightKg']).' kg' : ''],
            ['section' => 'algemeen', 'label' => 'Dagelijkse activiteit', 'value' => self::labelOf(self::ACTIVITY_LEVELS, $intake['activityLevel'])],
            ['section' => 'algemeen', 'label' => 'Medische aandachtspunten', 'value' => $intake['medical'] ?? ''],
            ['section' => 'training', 'label' => 'Trainingservaring', 'value' => self::labelOf(self::EXPERIENCE, $intake['experience'])],
            ['section' => 'training', 'label' => 'Trainingen per week', 'value' => $n($intake['trainingDays']).'×'],
            ['section' => 'training', 'label' => 'Duur per training', 'value' => $n($intake['sessionMinutes']).' minuten'],
            ['section' => 'training', 'label' => 'Trainingslocatie', 'value' => self::labelOf(self::LOCATIONS, $intake['location'])],
            ['section' => 'training', 'label' => 'Beschikbaar materiaal', 'value' => $intake['equipment'] ?? ''],
            ['section' => 'training', 'label' => 'Sport', 'value' => $intake['sport'] ?? ''],
            ['section' => 'training', 'label' => 'Blessures of beperkingen', 'value' => $intake['injuries'] ?? ''],
            ['section' => 'voeding', 'label' => 'Eetstijl', 'value' => self::labelOf(self::DIETS, $intake['diet'])],
            [
                'section' => 'voeding',
                'label' => 'Allergieën en intoleranties',
                'value' => implode(', ', array_filter($allergies, Js::truthy(...))),
            ],
            ['section' => 'voeding', 'label' => 'Lust niet', 'value' => $intake['dislikes'] ?? ''],
            ['section' => 'voeding', 'label' => 'Eetmomenten per dag', 'value' => $n($intake['mealsPerDay'])],
        ];

        return array_values(array_filter($rows, fn (array $r) => $r['value'] !== ''));
    }
}
