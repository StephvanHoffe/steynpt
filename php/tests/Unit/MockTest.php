<?php

namespace Tests\Unit;

use App\Support\Plans\Allergens;
use App\Support\Plans\Mock;
use App\Support\Plans\PlanSchema;
use PHPUnit\Framework\TestCase;

class MockTest extends TestCase
{
    public function test_testmodus_concepten_voldoen_aan_de_schemas(): void
    {
        $base = IntakeTest::base();
        [$training, $errors] = PlanSchema::parseTrainingPlan(Mock::mockTrainingPlan($base));
        $this->assertSame([], $errors);
        $this->assertCount(3, $training['days']);
        [$nutrition, $errors] = PlanSchema::parseNutritionPlan(Mock::mockNutritionPlan($base));
        $this->assertSame([], $errors);
        $this->assertCount(4, $nutrition['meals']);
        $this->assertGreaterThan(0, count(Allergens::findAllergenWarnings($nutrition, $base)), 'voorbeeld bevat bewust een conflict (walnoten)');
    }

    public function test_testmodus_inhoud(): void
    {
        $base = IntakeTest::base();
        $training = Mock::mockTrainingPlan($base);
        $this->assertSame('Dag 2 – Full body', $training['days'][1]['name']);
        $this->assertSame('3', $training['days'][0]['exercises'][0]['sets']);
        $this->assertSame('4', $training['days'][1]['exercises'][0]['sets'], 'vanaf dag 2 een extra set bij de eerste oefening');
        $this->assertSame('Goblet squat', $training['days'][0]['exercises'][0]['name']);
        $this->assertSame('Squat met eigen gewicht', Mock::mockTrainingPlan([...$base, 'location' => 'buiten'])['days'][0]['exercises'][0]['name']);
        $this->assertSame('Dit is een automatisch voorbeeld uit de testmodus, gebaseerd op 3 trainingen van 60 minuten per week.', $training['summary']);

        $nutrition = Mock::mockNutritionPlan($base, 2026);
        $this->assertSame(['calories' => 1610, 'protein' => 130, 'carbs' => 160, 'fat' => 50, 'water' => '2-2,5 liter per dag'], $nutrition['targets']);
        $this->assertSame([[403, 403], [403], [242], [403]], array_map(fn ($m) => array_column($m['options'], 'kcal'), $nutrition['meals']));
        $this->assertCount(6, Mock::mockNutritionPlan([...$base, 'mealsPerDay' => 6], 2026)['meals']);
    }
}
