<?php

namespace Tests\Unit;

use App\Support\Intake;
use PHPUnit\Framework\TestCase;

class IntakeTest extends TestCase
{
    public const RAW = [
        'wants' => ['training', 'voeding'],
        'goal' => 'afvallen',
        'sex' => 'vrouw',
        'birthYear' => 1994,
        'heightCm' => 170,
        'weightKg' => '72,5',
        'experience' => 'beginner',
        'trainingDays' => 3,
        'sessionMinutes' => 60,
        'location' => 'sportschool',
        'activityLevel' => 'zittend',
        'diet' => 'vegetarisch',
        'allergies' => ['noten', 'lactose'],
        'mealsPerDay' => 4,
    ];

    /** De gevalideerde basisintake uit plans.test.ts. */
    public static function base(): array
    {
        [$data, $errors] = Intake::validate(self::RAW);
        if ($data === null) {
            throw new \RuntimeException('Basisintake ongeldig: '.json_encode($errors));
        }

        return $data;
    }

    /** @return array<string, string> */
    private static function errors(array $overrides, array $remove = []): array
    {
        $input = array_diff_key([...self::RAW, ...$overrides], array_flip($remove));

        return Intake::validate($input, 2026)[1];
    }

    public function test_intake_komma_als_decimaal_en_optionele_velden(): void
    {
        $base = self::base();
        $this->assertSame(72.5, $base['weightKg']);
        $this->assertArrayNotHasKey('targetWeightKg', $base);
        $this->assertArrayNotHasKey('injuries', $base);
    }

    public function test_intake_genormaliseerde_gegevens_in_dezelfde_volgorde(): void
    {
        [$data, $errors] = Intake::validate([...self::RAW, 'birthYear' => ' 1990 ', 'targetWeightKg' => ' 65,5 ', 'goalDetails' => '  hoi  ', 'injuries' => '', 'foo' => 'bar'], 2026);
        $this->assertSame([], $errors);
        $this->assertSame([
            'wants' => ['training', 'voeding'], 'goal' => 'afvallen', 'goalDetails' => 'hoi', 'sex' => 'vrouw', 'birthYear' => 1990,
            'heightCm' => 170, 'weightKg' => 72.5, 'targetWeightKg' => 65.5, 'experience' => 'beginner', 'trainingDays' => 3,
            'sessionMinutes' => 60, 'location' => 'sportschool', 'activityLevel' => 'zittend', 'diet' => 'vegetarisch',
            'allergies' => ['noten', 'lactose'], 'mealsPerDay' => 4,
        ], $data);
        $this->assertArrayNotHasKey('targetWeightKg', Intake::validate([...self::RAW, 'targetWeightKg' => ''], 2026)[0], 'leeg streefgewicht telt als niet ingevuld');
        $this->assertSame([], Intake::validate(array_diff_key(self::RAW, ['allergies' => 1]), 2026)[0]['allergies'], 'standaard geen allergieën');
    }

    public function test_intake_formdata_met_meerdere_allergieen(): void
    {
        $raw = Intake::intakeFromFormData(['wants' => ['voeding'], 'allergies' => ['ei', 'soja'], 'consent' => 'on', '$ACTION_ID_x' => '1', 'goal' => 'fitter', 'medical' => null]);
        $this->assertSame(['ei', 'soja'], $raw['allergies']);
        $this->assertSame(['voeding'], $raw['wants']);
        $this->assertArrayNotHasKey('consent', $raw);
        $this->assertFalse((bool) array_filter(array_keys($raw), fn ($k) => str_starts_with($k, '$')));
        $this->assertSame('', $raw['medical'], 'leeg veld (null via Laravel) wordt weer lege tekst');
        $this->assertSame([], Intake::intakeFromFormData([])['wants'], 'niets aangevinkt: lege lijst');
        $this->assertSame(['training'], Intake::intakeFromFormData(['wants' => 'training'])['wants']);
    }

    public function test_intake_validatiefouten(): void
    {
        [$data, $errors] = Intake::validate([...self::base(), 'wants' => [], 'birthYear' => 2020, 'trainingDays' => 9]);
        $this->assertNull($data);
        $this->assertNotEmpty($errors);
        $this->assertSame(['wants' => 'Kies minimaal één schema', 'birthYear' => 'Je moet minimaal 14 jaar zijn', 'trainingDays' => 'Kies hoe vaak je per week wilt trainen'], self::errors(['wants' => [], 'birthYear' => 2020, 'trainingDays' => 9]));
    }

    public function test_intake_meldingen_per_veld_zoals_zod(): void
    {
        $this->assertSame([
            'wants' => 'Maak een geldige keuze',
            'goal' => 'Kies je belangrijkste doel',
            'sex' => 'Maak een keuze',
            'birthYear' => 'Vul je geboortejaar in',
            'heightCm' => 'Vul je lengte in',
            'weightKg' => 'Vul je gewicht in',
            'experience' => 'Kies je ervaring',
            'trainingDays' => 'Kies hoe vaak je per week wilt trainen',
            'sessionMinutes' => 'Kies hoe lang een training mag duren',
            'location' => 'Kies waar je traint',
            'activityLevel' => 'Kies hoe actief je dagelijks bent',
            'diet' => 'Kies je eetstijl',
            'mealsPerDay' => 'Kies het aantal eetmomenten',
        ], Intake::validate([], 2026)[1]);

        // Lege tekst wordt 0 (Number("")), dus een grensmelding.
        $this->assertSame('Vul een geldig geboortejaar in', self::errors(['birthYear' => ''])['birthYear']);
        $this->assertSame('Vul je lengte in centimeters in', self::errors(['heightCm' => ''])['heightCm']);
        $this->assertSame('Vul een geldig gewicht in', self::errors(['weightKg' => ''])['weightKg']);
        $this->assertSame('Vul je geboortejaar in', self::errors(['birthYear' => '1990.5'])['birthYear']);
        $this->assertSame('Vul je geboortejaar in', self::errors(['birthYear' => 'abc'])['birthYear']);
        $this->assertSame('Vul je geboortejaar in', self::errors(['birthYear' => '1e20'])['birthYear']);
        $this->assertSame('Vul een geldig geboortejaar in', self::errors(['birthYear' => 1935])['birthYear']);
        $this->assertSame([], self::errors(['birthYear' => 1936]));
        $this->assertSame([], self::errors(['birthYear' => 2012]));
        $this->assertSame('Je moet minimaal 14 jaar zijn', self::errors(['birthYear' => 2013])['birthYear']);
        $this->assertSame('Vul je lengte in hele centimeters in', self::errors(['heightCm' => '170.5'])['heightCm']);
        $this->assertSame('Vul je lengte in', self::errors(['heightCm' => '170,0'])['heightCm'], 'lengte kent geen komma');
        $this->assertSame('Vul je gewicht in', self::errors(['weightKg' => '1,2,3'])['weightKg']);
        $this->assertSame('Vul een geldig gewicht in', self::errors(['weightKg' => '10'])['weightKg']);
        $this->assertSame('Vul een geldig streefgewicht in', self::errors(['targetWeightKg' => '10'])['targetWeightKg']);
        $this->assertSame('Vul een geldig streefgewicht in', self::errors(['targetWeightKg' => ' '])['targetWeightKg']);
        $this->assertSame('Vul een geldig streefgewicht in', self::errors(['targetWeightKg' => '400'])['targetWeightKg']);
        $this->assertSame('Vul een geldig streefgewicht in', self::errors(['targetWeightKg' => 'abc'])['targetWeightKg']);
        $this->assertSame('Kies hoe vaak je per week wilt trainen', self::errors(['trainingDays' => '2.5'])['trainingDays']);
        $this->assertSame('Kies hoe lang een training mag duren', self::errors(['sessionMinutes' => '50'])['sessionMinutes']);
        $this->assertSame([], self::errors(['sessionMinutes' => ' 45 ']));
        $this->assertSame('Kies het aantal eetmomenten', self::errors(['mealsPerDay' => '7'])['mealsPerDay']);
        $this->assertSame('Maximaal 200 tekens', self::errors(['sport' => str_repeat('x', 201)])['sport']);
        $this->assertSame([], self::errors(['sport' => str_repeat('😀', 200)]), 'lengte in tekens');
        $this->assertSame('Controleer dit veld', self::errors(['equipment' => 5])['equipment']);
        $this->assertSame('Controleer dit veld', self::errors(['goalDetails' => null])['goalDetails']);
        $this->assertSame('Maak een geldige keuze', self::errors(['wants' => 'training'])['wants']);
        $this->assertSame('Maak een geldige keuze', self::errors(['wants' => ['x', 'training']])['wants']);
        $this->assertSame('Maak een geldige keuze', self::errors(['allergies' => null])['allergies']);
        $this->assertSame('Maak een geldige keuze', self::errors(['allergies' => ['noten', 'kaas']])['allergies']);
        $this->assertSame('Kies je belangrijkste doel', self::errors(['goal' => 'x'])['goal']);
    }

    public function test_richtwaarden_tekort_bij_afvallen_nooit_onder_rustmetabolisme(): void
    {
        $base = self::base();
        $t = Intake::estimateTargets($base, 2026);
        $this->assertLessThan($t['maintenance'], $t['calories'], 'tekort bij afvallen');
        $this->assertGreaterThanOrEqual($t['bmr'], $t['calories'], 'niet onder BMR');
        $this->assertGreaterThanOrEqual(1200, $t['calories']);
        $this->assertSame(130, $t['protein']); // 1.8 g/kg × 72.5 kg, afgerond op 5
        $kcal = $t['protein'] * 4 + $t['carbs'] * 4 + $t['fat'] * 9;
        $this->assertLessThan(60, abs($kcal - $t['calories']), "macro's tellen op tot de energie");
        $bulk = Intake::estimateTargets([...$base, 'goal' => 'spieropbouw', 'sex' => 'man'], 2026);
        $this->assertGreaterThan($bulk['maintenance'], $bulk['calories']);

        $this->assertSame(['bmr' => 1470, 'maintenance' => 1890, 'calories' => 1610, 'protein' => 130, 'carbs' => 160, 'fat' => 50], $t);
        $this->assertSame(['bmr' => 1630, 'maintenance' => 2110, 'calories' => 2320, 'protein' => 130, 'carbs' => 285, 'fat' => 70], $bulk);
    }

    public function test_samenvatting_en_labels(): void
    {
        $rows = Intake::intakeSummary([...self::base(), 'targetWeightKg' => 65.5, 'allergiesOther' => 'Kiwi', 'medical' => 'Astma'], 2026);
        $values = array_column($rows, 'value', 'label');
        $this->assertSame('Trainingsschema, Voedingsschema', $values["Gewenste schema's"]);
        $this->assertSame('32 jaar', $values['Leeftijd']);
        $this->assertSame('72.5 kg', $values['Gewicht']);
        $this->assertSame('65.5 kg', $values['Streefgewicht']);
        $this->assertSame('3×', $values['Trainingen per week']);
        $this->assertSame('Noten, Lactose, Kiwi', $values['Allergieën en intoleranties']);
        $this->assertSame('4', $values['Eetmomenten per dag']);
        $this->assertArrayNotHasKey('Toelichting doel', $values, 'lege waarden vallen weg');
        $this->assertCount(16, $rows);
        $this->assertSame(['section' => 'algemeen', 'label' => "Gewenste schema's", 'value' => 'Trainingsschema, Voedingsschema'], $rows[0]);

        $this->assertSame('Afvallen', Intake::labelOf(Intake::GOALS, 'afvallen'));
        $this->assertSame('x', Intake::labelOf(Intake::GOALS, 'x'));
        $this->assertSame('', Intake::labelOf(Intake::GOALS, null));
    }
}
