<?php

namespace Tests\Unit;

use App\Support\Plans\Prompt;
use PHPUnit\Framework\TestCase;

/** De prompt voor een klant die Mijn omgeving in het Engels gebruikt: het hele schema in het Engels. */
class PromptEnglishTest extends TestCase
{
    public function test_engelse_voedingsprompt(): void
    {
        $base = IntakeTest::base();
        ['system' => $system, 'user' => $user] = Prompt::buildPlanPrompt('voeding', [...$base, 'medical' => 'Hoge bloeddruk'], 'Minder koolhydraten in de avond', 2026, 'en');

        $this->assertStringStartsWith('You are drafting a plan for a client of SteynPT', $system);
        $this->assertStringContainsString('Write the whole plan in English (British spelling): every text field', $system);
        $this->assertStringContainsString('do not copy Dutch words into the plan', $system);
        $this->assertStringContainsString('Allergies, intolerances and eating style are strict requirements.', $system);
        $this->assertStringContainsString('available in Dutch supermarkets', $system);
        $this->assertStringNotContainsString('Schrijf in het Nederlands', $system);
        $this->assertStringNotContainsString('Create a training plan', $system);

        $this->assertSame(implode("\n\n", [
            "Client intake (in Dutch):\n- Gewenste schema's: Trainingsschema, Voedingsschema\n- Doel: Afvallen\n- Geslacht: Vrouw\n- Leeftijd: 32 jaar\n- Lengte: 170 cm\n- Gewicht: 72.5 kg\n- Dagelijkse activiteit: Vooral zittend\n- Medische aandachtspunten: Hoge bloeddruk\n- Eetstijl: Vegetarisch\n- Allergieën en intoleranties: Noten, Lactose\n- Eetmomenten per dag: 4",
            "Calculated targets (Mifflin-St Jeor with activity factor):\n- Resting metabolic rate: 1470 kcal\n- Maintenance: 1890 kcal\n- Target energy: 1610 kcal\n- Protein: 130 g\n- Carbohydrates: 160 g\n- Fat: 50 g",
            'The client trains 3× per week, 60 minutes per session.',
            "Additional instruction from Steyn (takes precedence; may be written in Dutch):\nMinder koolhydraten in de avond",
            'Now create the nutrition plan. Write all of it in English.',
        ]), $user);
    }

    public function test_engelse_trainingsprompt(): void
    {
        $base = IntakeTest::base();
        ['system' => $system, 'user' => $user] = Prompt::buildPlanPrompt('training', $base, "  \n ", 2026, 'en');

        $this->assertStringContainsString('Write the whole plan in English', $system);
        $this->assertStringContainsString('Create a training plan:', $system);
        $this->assertStringNotContainsString('Create a nutrition plan', $system);
        $this->assertStringStartsWith("Client intake (in Dutch):\n", $user);
        $this->assertStringContainsString('- Trainingen per week: 3×', $user);
        $this->assertStringNotContainsString('Allergieën', $user, 'voedingsvragen niet in de trainingsprompt');
        $this->assertStringNotContainsString('Additional instruction', $user, 'lege instructie wordt overgeslagen');
        $this->assertStringEndsWith("Trainingslocatie: Sportschool\n\nNow create the training plan. Write all of it in English.", $user);
    }

    public function test_nederlands_is_de_standaard(): void
    {
        $base = IntakeTest::base();
        foreach (['training', 'voeding'] as $type) {
            $this->assertSame(Prompt::buildPlanPrompt($type, $base, 'Focus op benen', 2026), Prompt::buildPlanPrompt($type, $base, 'Focus op benen', 2026, 'nl'));
            $this->assertNotSame(Prompt::buildPlanPrompt($type, $base, null, 2026), Prompt::buildPlanPrompt($type, $base, null, 2026, 'en'));
        }
    }
}
