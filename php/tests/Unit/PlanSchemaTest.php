<?php

namespace Tests\Unit;

use App\Support\Plans\PlanSchema;
use PHPUnit\Framework\TestCase;

class PlanSchemaTest extends TestCase
{
    public function test_lege_schemas_zijn_geldig(): void
    {
        [$training, $errors] = PlanSchema::parseTrainingPlan(PlanSchema::emptyTrainingPlan());
        $this->assertSame([], $errors);
        $this->assertSame(PlanSchema::emptyTrainingPlan(), $training);
        $this->assertCount(3, $training['days']);
        $this->assertSame('Dag 3', $training['days'][2]['name']);

        [$nutrition, $errors] = PlanSchema::parseNutritionPlan(PlanSchema::emptyNutritionPlan());
        $this->assertSame([], $errors);
        $this->assertSame(['Ontbijt', 'Lunch', 'Avondeten'], array_column($nutrition['meals'], 'name'));
        $this->assertSame('2-2,5 liter per dag', $nutrition['targets']['water']);
    }

    public function test_lege_onderdelen_voor_de_editor(): void
    {
        $this->assertSame(['name' => '', 'sets' => '3', 'reps' => '10', 'rest' => '90 sec', 'notes' => ''], PlanSchema::emptyExercise());
        $this->assertSame(['title' => '', 'ingredients' => '', 'kcal' => 0, 'protein' => 0], PlanSchema::emptyMealOption());
        $this->assertSame(['name' => '', 'time' => '', 'options' => [PlanSchema::emptyMealOption()]], PlanSchema::emptyMeal());
        $this->assertSame(['name' => 'Dag 2', 'focus' => '', 'warmup' => '', 'exercises' => [PlanSchema::emptyExercise()], 'cooldown' => ''], PlanSchema::emptyTrainingDay(2));
        $this->assertSame([], PlanSchema::emptyTrainingPlan(0)['days']);
        // Richtwaarden worden overgenomen (zoals de spread in TypeScript, extra sleutels incl.).
        $this->assertSame(
            ['calories' => 1800, 'protein' => 130, 'carbs' => 200, 'fat' => 55, 'water' => '2-2,5 liter per dag', 'bmr' => 1400, 'maintenance' => 2100],
            PlanSchema::emptyNutritionPlan(['calories' => 1800, 'protein' => 130, 'carbs' => 200, 'fat' => 55, 'bmr' => 1400, 'maintenance' => 2100])['targets'],
        );
    }

    public function test_onbekende_sleutels_vallen_weg_en_volgorde_volgt_het_schema(): void
    {
        $input = [
            'tips' => [], 'progression' => '', 'extra' => 1, 'title' => 'T', 'summary' => '', 'daysPerWeek' => 1, 'durationWeeks' => 6.5,
            'days' => [['cooldown' => '', 'bar' => 1, 'name' => 'a', 'focus' => 'b', 'warmup' => '', 'exercises' => [['notes' => '', 'foo' => 2, 'name' => 'x', 'sets' => '3', 'reps' => '1', 'rest' => '']]]],
        ];
        [$data, $errors] = PlanSchema::parse('training', $input);
        $this->assertSame([], $errors);
        $this->assertSame([
            'title' => 'T', 'summary' => '', 'durationWeeks' => 6.5, 'daysPerWeek' => 1,
            'days' => [['name' => 'a', 'focus' => 'b', 'warmup' => '', 'exercises' => [['name' => 'x', 'sets' => '3', 'reps' => '1', 'rest' => '', 'notes' => '']], 'cooldown' => '']],
            'progression' => '', 'tips' => [],
        ], $data);
        $this->assertSame(
            json_encode($data),
            json_encode(PlanSchema::parse('training', json_decode(json_encode($input)))[0]),
            'ook JSON als objecten (stdClass)',
        );
    }

    public function test_foutmeldingen_per_pad_zoals_zod(): void
    {
        $this->assertSame([
            'title' => 'Invalid input: expected string, received undefined',
            'summary' => 'Invalid input: expected string, received undefined',
            'durationWeeks' => 'Invalid input: expected number, received undefined',
            'daysPerWeek' => 'Invalid input: expected number, received undefined',
            'days' => 'Invalid input: expected array, received undefined',
            'progression' => 'Invalid input: expected string, received undefined',
            'tips' => 'Invalid input: expected array, received undefined',
        ], PlanSchema::parseTrainingPlan([])[1]);

        $this->assertSame(['' => 'Invalid input: expected object, received null'], PlanSchema::parseTrainingPlan(null)[1]);
        $this->assertSame(['' => 'Invalid input: expected object, received string'], PlanSchema::parseTrainingPlan('x')[1]);
        $this->assertSame(['' => 'Invalid input: expected object, received array'], PlanSchema::parseTrainingPlan([1, 2])[1]);

        $this->assertSame([
            'title' => 'Invalid input: expected string, received number',
            'summary' => 'Invalid input: expected string, received null',
            'durationWeeks' => 'Invalid input: expected number, received string',
            'daysPerWeek' => 'Invalid input: expected number, received boolean',
            'days' => 'Invalid input: expected array, received object',
            'progression' => 'Invalid input: expected string, received array',
            'tips.1' => 'Invalid input: expected string, received number',
            'tips.2' => 'Invalid input: expected string, received null',
        ], PlanSchema::parseTrainingPlan(['title' => 1, 'summary' => null, 'durationWeeks' => '6', 'daysPerWeek' => true, 'days' => ['a' => 1], 'progression' => [1], 'tips' => ['a', 2, null]])[1]);

        [$data, $errors] = PlanSchema::parseTrainingPlan([...PlanSchema::emptyTrainingPlan(1), 'days' => [null, 'x', ['name' => 'a'], ['name' => 'a', 'focus' => '', 'warmup' => '', 'cooldown' => '', 'exercises' => [['name' => 'x'], 1]]]]);
        $this->assertNull($data);
        $this->assertSame([
            'days.0' => 'Invalid input: expected object, received null',
            'days.1' => 'Invalid input: expected object, received string',
            'days.2.focus' => 'Invalid input: expected string, received undefined',
            'days.2.warmup' => 'Invalid input: expected string, received undefined',
            'days.2.exercises' => 'Invalid input: expected array, received undefined',
            'days.2.cooldown' => 'Invalid input: expected string, received undefined',
            'days.3.exercises.0.sets' => 'Invalid input: expected string, received undefined',
            'days.3.exercises.0.reps' => 'Invalid input: expected string, received undefined',
            'days.3.exercises.0.rest' => 'Invalid input: expected string, received undefined',
            'days.3.exercises.0.notes' => 'Invalid input: expected string, received undefined',
            'days.3.exercises.1' => 'Invalid input: expected object, received number',
        ], $errors);

        $this->assertSame([
            'targets.calories' => 'Invalid input: expected number, received string',
            'targets.protein' => 'Invalid input: expected number, received null',
            'targets.water' => 'Invalid input: expected string, received number',
            'meals.0.options.0.kcal' => 'Invalid input: expected number, received string',
            'avoid' => 'Invalid input: expected array, received string',
        ], PlanSchema::parse('voeding', [
            ...PlanSchema::emptyNutritionPlan(),
            'targets' => ['calories' => '1', 'protein' => null, 'carbs' => 1.5, 'fat' => 2, 'water' => 2],
            'meals' => [['name' => '', 'time' => '', 'options' => [['title' => '', 'ingredients' => '', 'kcal' => 'x', 'protein' => 3.25]]]],
            'avoid' => 'x',
        ])[1]);
        $this->assertSame(['targets.calories' => 'Invalid input: expected number, received Infinity'], PlanSchema::parse('voeding', [...PlanSchema::emptyNutritionPlan(), 'targets' => [...PlanSchema::emptyNutritionPlan()['targets'], 'calories' => INF]])[1]);
    }

    public function test_schema_per_type(): void
    {
        $this->assertNotEmpty(PlanSchema::parse('voeding', PlanSchema::emptyTrainingPlan())[1]);
        $this->assertNotEmpty(PlanSchema::parse('training', PlanSchema::emptyNutritionPlan())[1]);
        $this->assertSame(PlanSchema::TRAINING_PLAN, PlanSchema::planSchemaFor('training'));
        $this->assertSame(PlanSchema::NUTRITION_PLAN, PlanSchema::planSchemaFor('voeding'));
    }

    public function test_json_schema_voor_de_ai(): void
    {
        $schema = PlanSchema::jsonSchema('training');
        $this->assertSame('object', $schema['type']);
        $this->assertFalse($schema['additionalProperties']);
        $this->assertSame(['title', 'summary', 'durationWeeks', 'daysPerWeek', 'days', 'progression', 'tips'], $schema['required']);
        $this->assertSame('Hoeveel weken dit schema gevolgd wordt voor een evaluatie', $schema['properties']['durationWeeks']['description']);
        $this->assertSame("Aantal sets, bijv. '3' of '3-4'", $schema['properties']['days']['items']['properties']['exercises']['items']['properties']['sets']['description']);
        $nutrition = PlanSchema::jsonSchema('voeding');
        $this->assertSame(['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Wat de klant moet vermijden, o.a. op basis van allergieën en eetstijl'], $nutrition['properties']['avoid']);
        $this->assertSame(['calories', 'protein', 'carbs', 'fat', 'water'], $nutrition['properties']['targets']['required']);
    }
}
