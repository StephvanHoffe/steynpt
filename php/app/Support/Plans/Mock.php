<?php

declare(strict_types=1);

namespace App\Support\Plans;

use App\Support\Intake;
use App\Support\Js;

/**
 * Voorbeeldconcepten voor de testmodus (AI_MOCK=1), zodat de volledige flow
 * zonder API-sleutel lokaal te testen is. Nooit bedoeld voor echte klanten.
 */
final class Mock
{
    private const GYM = [
        ['name' => 'Goblet squat', 'sets' => '3', 'reps' => '10-12', 'rest' => '90 sec', 'notes' => 'Borst omhoog, knieën in lijn met je tenen.'],
        ['name' => 'Roeien met dumbbell', 'sets' => '3', 'reps' => '10 per kant', 'rest' => '60 sec', 'notes' => ''],
        ['name' => 'Dumbbell bench press', 'sets' => '3', 'reps' => '8-10', 'rest' => '90 sec', 'notes' => ''],
        ['name' => 'Roemeense deadlift', 'sets' => '3', 'reps' => '10', 'rest' => '90 sec', 'notes' => 'Rug neutraal, beweeg vanuit de heupen.'],
        ['name' => 'Plank', 'sets' => '3', 'reps' => '30-45 sec', 'rest' => '45 sec', 'notes' => ''],
    ];

    private const HOME = [
        ['name' => 'Squat met eigen gewicht', 'sets' => '3', 'reps' => '15', 'rest' => '60 sec', 'notes' => ''],
        ['name' => 'Push-up (eventueel op knieën)', 'sets' => '3', 'reps' => '8-12', 'rest' => '60 sec', 'notes' => ''],
        ['name' => 'Glute bridge', 'sets' => '3', 'reps' => '15', 'rest' => '45 sec', 'notes' => ''],
        ['name' => 'Lunges', 'sets' => '3', 'reps' => '10 per been', 'rest' => '60 sec', 'notes' => ''],
        ['name' => 'Dead bug', 'sets' => '3', 'reps' => '10 per kant', 'rest' => '45 sec', 'notes' => ''],
    ];

    /** @param  array<string, mixed>  $intake  gevalideerde intake */
    public static function mockTrainingPlan(array $intake): array
    {
        $exercises = $intake['location'] === 'sportschool' ? self::GYM : self::HOME;
        $days = (int) $intake['trainingDays'];

        return [
            'title' => 'Voorbeeldschema training (testmodus)',
            'summary' => 'Dit is een automatisch voorbeeld uit de testmodus, gebaseerd op '.Js::numberToString($intake['trainingDays']).' trainingen van '.Js::numberToString($intake['sessionMinutes']).' minuten per week.',
            'durationWeeks' => 6,
            'daysPerWeek' => $intake['trainingDays'],
            'days' => array_map(fn (int $i) => [
                'name' => 'Dag '.($i + 1).' – Full body',
                'focus' => 'Kracht en techniek',
                'warmup' => '5 minuten fietsen of touwtjespringen, daarna mobiliteit voor heupen en schouders.',
                'exercises' => array_map(
                    fn (array $e, int $j) => [...$e, 'sets' => $j === 0 && $i > 0 ? '4' : $e['sets']],
                    $exercises,
                    array_keys($exercises),
                ),
                'cooldown' => '5 minuten rustig uitlopen en rekken.',
            ], $days > 0 ? range(0, $days - 1) : []),
            'progression' => 'Lukt het om alle sets met goede techniek af te ronden? Verhoog dan de week erna het gewicht of het aantal herhalingen.',
            'tips' => ['Noteer je gewichten na elke training.', 'Slaap minimaal 7 uur voor optimaal herstel.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $intake  gevalideerde intake
     * @param  int|null  $year  huidig jaar (standaard: nu), voor de richtwaarden
     */
    public static function mockNutritionPlan(array $intake, ?int $year = null): array
    {
        $t = Intake::estimateTargets($intake, $year);
        $perMeal = Js::num(Js::round($t['calories'] / $intake['mealsPerDay']));
        $meals = [
            ['name' => 'Ontbijt', 'time' => '07:30', 'options' => [['title' => 'Yoghurtbowl', 'ingredients' => '200 g Griekse yoghurt, 40 g havermout, 15 g walnoten, blauwe bessen, theelepel honing', 'kcal' => $perMeal, 'protein' => 25], ['title' => 'Volkoren boterhammen', 'ingredients' => '2 volkoren boterhammen met hüttenkäse en tomaat', 'kcal' => $perMeal, 'protein' => 22]]],
            ['name' => 'Lunch', 'time' => '12:30', 'options' => [['title' => 'Salade met kip', 'ingredients' => 'Gemengde sla, 120 g kipfilet, kikkererwten, komkommer, olijfolie', 'kcal' => $perMeal, 'protein' => 35]]],
            ['name' => 'Tussendoor', 'time' => '15:30', 'options' => [['title' => 'Fruit en kwark', 'ingredients' => 'Appel en 150 g magere kwark', 'kcal' => Js::num(Js::round($perMeal * 0.6)), 'protein' => 18]]],
            ['name' => 'Avondeten', 'time' => '18:30', 'options' => [['title' => 'Zalm met groenten', 'ingredients' => '150 g zalm, 75 g zilvervliesrijst, broccoli', 'kcal' => $perMeal, 'protein' => 38]]],
            ['name' => 'Avondsnack', 'time' => '21:00', 'options' => [['title' => 'Eiwitrijke snack', 'ingredients' => 'Skyr met kaneel', 'kcal' => Js::num(Js::round($perMeal * 0.5)), 'protein' => 15]]],
            ['name' => 'Na de training', 'time' => 'Na het trainen', 'options' => [['title' => 'Herstelshake', 'ingredients' => 'Banaan, 30 g whey, 250 ml melk', 'kcal' => Js::num(Js::round($perMeal * 0.7)), 'protein' => 30]]],
        ];

        return [
            'title' => 'Voorbeeldschema voeding (testmodus)',
            'summary' => 'Dit is een automatisch voorbeeld uit de testmodus met de berekende richtwaarden.',
            'targets' => ['calories' => $t['calories'], 'protein' => $t['protein'], 'carbs' => $t['carbs'], 'fat' => $t['fat'], 'water' => '2-2,5 liter per dag'],
            'meals' => array_slice($meals, 0, max(0, (int) $intake['mealsPerDay'])),
            'avoid' => ['Controleer altijd de etiketten op allergenen.'],
            'tips' => ['Eet bij elk eetmoment een eiwitbron.', 'Bereid je lunch de avond ervoor voor.'],
        ];
    }
}
