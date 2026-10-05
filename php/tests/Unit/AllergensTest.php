<?php

namespace Tests\Unit;

use App\Support\Plans\Allergens;
use App\Support\Plans\PlanSchema;
use PHPUnit\Framework\TestCase;

class AllergensTest extends TestCase
{
    /** Voedingsschema met één eetmoment en een optie per ingrediëntregel. */
    private static function plan(array $ingredients, array $extra = []): array
    {
        return [
            ...PlanSchema::emptyNutritionPlan(),
            'meals' => [[
                'name' => 'Ontbijt',
                'time' => '08:00',
                'options' => array_map(fn (string $i, int $n) => ['title' => 'Optie '.($n + 1), 'ingredients' => $i, 'kcal' => 400, 'protein' => 20], $ingredients, array_keys($ingredients)),
            ]],
            ...$extra,
        ];
    }

    public function test_allergenen_vindt_noten_lactose_en_vlees_bij_vegetarier(): void
    {
        $warnings = Allergens::findAllergenWarnings(
            self::plan(['Havermout met walnoten', 'Volkoren brood met kipfilet', 'Kwark met fruit']),
            ['allergies' => ['noten', 'lactose'], 'diet' => 'vegetarisch'],
        );
        $this->assertSame(
            ['Ontbijt › Optie 1|allergie: noten|walnoten', 'Ontbijt › Optie 2|eetstijl: vegetarisch|kipfilet', 'Ontbijt › Optie 3|allergie: lactose|kwark'],
            array_map(fn ($w) => "{$w['where']}|{$w['reason']}|{$w['term']}", $warnings),
        );
    }

    public function test_allergenen_geen_valse_meldingen(): void
    {
        $warnings = Allergens::findAllergenWarnings(
            self::plan([
                'Lactosevrije kwark met eiwitpoeder en havermelk',
                'Glutenvrij brood zonder noten',
                'Vegetarische kipstukjes met rijst',
                'Kokosyoghurt en een eigen recept',
            ]),
            ['allergies' => ['lactose', 'noten', 'ei'], 'diet' => 'vegetarisch'],
        );
        $this->assertSame([], $warnings);
    }

    public function test_allergenen_lijst_avoid_wordt_niet_gecontroleerd_zonder_geldt_alleen_direct_ervoor(): void
    {
        $this->assertSame([], Allergens::findAllergenWarnings(self::plan([], ['avoid' => ["Pinda's en noten"]]), ['allergies' => ['pinda', 'noten'], 'diet' => 'alles']));
        $w = Allergens::findAllergenWarnings(self::plan(['Kwark zonder suiker, met noten']), ['allergies' => ['noten'], 'diet' => 'alles']);
        $this->assertCount(1, $w);
    }

    public function test_allergenen_woorden_die_alleen_op_een_trefwoord_lijken(): void
    {
        $context = ['allergies' => ['lactose', 'noten'], 'diet' => 'vegetarisch'];
        $this->assertSame([], Allergens::findAllergenWarnings(self::plan(['2 volkoren boterhammen met hummus', 'Speculaas en een snufje nootmuskaat']), $context));
        $this->assertCount(0, Allergens::findAllergenWarnings(self::plan(['Hamburger met friet']), ['allergies' => [], 'diet' => 'halal']));
        $this->assertCount(1, Allergens::findAllergenWarnings(self::plan(['Broodje met spek']), ['allergies' => [], 'diet' => 'halal']));
        $this->assertCount(1, Allergens::findAllergenWarnings(self::plan(['Roomboter op brood']), ['allergies' => ['melk'], 'diet' => 'alles']));
        $this->assertSame('boterhammen', Allergens::findAllergenWarnings(self::plan(['2 volkoren boterhammen']), ['allergies' => ['gluten'], 'diet' => 'alles'])[0]['term'] ?? null);
    }

    public function test_allergenen_ei_als_los_woord_niet_in_eiwit(): void
    {
        $this->assertCount(0, Allergens::findAllergenWarnings(self::plan(['Eiwitshake']), ['allergies' => ['ei'], 'diet' => 'alles']));
        $this->assertCount(1, Allergens::findAllergenWarnings(self::plan(['2 gekookte eieren']), ['allergies' => ['ei'], 'diet' => 'alles']));
        $this->assertCount(1, Allergens::findAllergenWarnings(self::plan(['1 ei met spinazie']), ['allergies' => [], 'diet' => 'veganistisch']));
    }

    public function test_allergenen_vervangers_hoofdletters_tips_en_toelichting(): void
    {
        $this->assertSame([], Allergens::findAllergenWarnings(self::plan(['Visvervanger met groenten', 'Kaas-vervanger']), ['allergies' => ['vis', 'melk'], 'diet' => 'alles']));
        $this->assertSame('ei', Allergens::findAllergenWarnings(self::plan(['ÉÉN EI']), ['allergies' => ['ei'], 'diet' => 'alles'])[0]['term']);
        $this->assertSame('crème fraîche', Allergens::findAllergenWarnings(self::plan(['Crème fraîche']), ['allergies' => ['melk'], 'diet' => 'alles'])[0]['term'], 'trefwoord met spatie en accenten');
        $warnings = Allergens::findAllergenWarnings(
            [...self::plan([]), 'meals' => [['name' => '', 'time' => '', 'options' => [['title' => '', 'ingredients' => 'tofu', 'kcal' => 0, 'protein' => 0]]]], 'tips' => ['Neem pinda\'s mee'], 'summary' => 'Met zalm'],
            ['allergies' => ['soja', 'pinda', 'vis'], 'diet' => 'alles'],
        );
        $this->assertSame(
            ['Maaltijd 1 › optie 1|allergie: soja|tofu', 'Tip 1|allergie: pinda|pinda', 'Toelichting|allergie: vis|zalm'],
            array_map(fn ($w) => "{$w['where']}|{$w['reason']}|{$w['term']}", $warnings),
        );
    }

    public function test_allergenen_ook_in_een_engels_voedingsschema(): void
    {
        $warnings = Allergens::findAllergenWarnings(
            self::plan(['Porridge with walnuts', 'Wholegrain bread with chicken breast', 'Greek yogurt with berries', 'Scrambled eggs on toast']),
            ['allergies' => ['noten', 'lactose', 'ei'], 'diet' => 'vegetarisch'],
        );
        $this->assertSame(
            ['Ontbijt › Optie 1|allergie: noten|walnuts', 'Ontbijt › Optie 2|eetstijl: vegetarisch|chicken', 'Ontbijt › Optie 3|allergie: lactose|yogurt', 'Ontbijt › Optie 4|allergie: ei|eggs'],
            array_map(fn ($w) => "{$w['where']}|{$w['reason']}|{$w['term']}", $warnings),
        );
    }

    public function test_allergenen_engels_zonder_valse_meldingen(): void
    {
        $warnings = Allergens::findAllergenWarnings(
            self::plan(['Lactose-free yogurt with oats', 'Dairy-free cheese on rice cakes', 'Butternut squash soup without nuts', 'Plant-based chicken pieces with rice', 'Nutritional yeast on vegetables']),
            ['allergies' => ['lactose', 'noten'], 'diet' => 'vegetarisch'],
        );
        $this->assertSame([], $warnings);
        // Melkallergie: lactosevrij is nog steeds melk.
        $this->assertCount(1, Allergens::findAllergenWarnings(self::plan(['Lactose-free yogurt']), ['allergies' => ['melk'], 'diet' => 'alles']));
    }
}
