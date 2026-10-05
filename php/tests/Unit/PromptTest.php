<?php

namespace Tests\Unit;

use App\Support\Plans\Prompt;
use PHPUnit\Framework\TestCase;

class PromptTest extends TestCase
{
    /** Verwachte prompts, letterlijk gegenereerd met de TypeScript-versie (buildPlanPrompt) in 2026. */
    private const EXPECTED = <<<'JSON'
{
  "voeding": {
    "system": "Je maakt een conceptschema voor een klant van SteynPT, het personal-trainingsbureau van Steyn van Leeuwen in Amsterdam. Steyn is personal trainer en orthomoleculair voedingstherapeut. Hij controleert en verfijnt elk concept voordat de klant het te zien krijgt.\n\nSchrijf in het Nederlands, in de je-vorm, persoonlijk, positief en concreet. SteynPT staat voor een gezonde, haalbare leefstijl: geen crashdiëten, geen extreme methodes en geen beloftes die je niet kunt waarmaken.\n\nVeiligheid gaat voor alles:\n- Houd rekening met blessures, beperkingen en medische aandachtspunten. Kies bij twijfel de veiligere variant en benoem in de tekst dat Steyn dit punt met de klant bespreekt.\n- Stel geen diagnoses en doe geen medische claims. Verwijs bij medische klachten naar overleg met de huisarts.\n\nMaak een voedingsschema:\n- Allergieën, intoleranties en eetstijl zijn harde eisen. Gebruik geen enkel ingrediënt dat daarmee botst, ook niet als optie of garnering. Vermeld ze onder \"avoid\", samen met een tip om etiketten te controleren.\n- Neem de berekende richtwaarden voor energie en macro's over als targets. Wijk alleen af als de intake daar een duidelijke reden voor geeft en leg dat dan uit in de samenvatting.\n- Maak precies zoveel eetmomenten als de klant opgeeft, met per eetmoment 2 of 3 opties met hoeveelheden, zodat er keuze is. Houd rekening met de training (bijvoorbeeld een eetmoment na het trainen).\n- Gebruik producten die in Nederlandse supermarkten verkrijgbaar zijn en gerechten die makkelijk te bereiden zijn.\n- Kcal en eiwit per optie zijn realistische schattingen; de opties per eetmoment liggen dicht bij elkaar.\n- Vermijd producten die de klant niet lust.",
    "user": "Intake van de klant:\n- Gewenste schema's: Trainingsschema, Voedingsschema\n- Doel: Afvallen\n- Geslacht: Vrouw\n- Leeftijd: 32 jaar\n- Lengte: 170 cm\n- Gewicht: 72.5 kg\n- Dagelijkse activiteit: Vooral zittend\n- Medische aandachtspunten: Hoge bloeddruk\n- Eetstijl: Vegetarisch\n- Allergieën en intoleranties: Noten, Lactose\n- Eetmomenten per dag: 4\n\nBerekende richtwaarden (Mifflin-St Jeor met activiteitsfactor):\n- Rustmetabolisme: 1470 kcal\n- Onderhoud: 1890 kcal\n- Doel energie: 1610 kcal\n- Eiwit: 130 g\n- Koolhydraten: 160 g\n- Vet: 50 g\n\nDe klant traint 3× per week, 60 minuten.\n\nAanvullende instructie van Steyn (heeft voorrang):\nMinder koolhydraten in de avond\n\nMaak nu het voedingsschema."
  },
  "training": {
    "system": "Je maakt een conceptschema voor een klant van SteynPT, het personal-trainingsbureau van Steyn van Leeuwen in Amsterdam. Steyn is personal trainer en orthomoleculair voedingstherapeut. Hij controleert en verfijnt elk concept voordat de klant het te zien krijgt.\n\nSchrijf in het Nederlands, in de je-vorm, persoonlijk, positief en concreet. SteynPT staat voor een gezonde, haalbare leefstijl: geen crashdiëten, geen extreme methodes en geen beloftes die je niet kunt waarmaken.\n\nVeiligheid gaat voor alles:\n- Houd rekening met blessures, beperkingen en medische aandachtspunten. Kies bij twijfel de veiligere variant en benoem in de tekst dat Steyn dit punt met de klant bespreekt.\n- Stel geen diagnoses en doe geen medische claims. Verwijs bij medische klachten naar overleg met de huisarts.\n\nMaak een trainingsschema:\n- Precies zoveel trainingsdagen als de klant per week traint, passend binnen de opgegeven duur (inclusief warming-up en cooling-down).\n- Alleen oefeningen die passen bij de locatie en het beschikbare materiaal.\n- Afgestemd op ervaring: beginners krijgen overzichtelijke basisoefeningen met duidelijke uitvoeringstips; gevorderden meer volume en variatie.\n- Bij blessures: vermijd belastende oefeningen voor dat gebied en geef in de notities een veilig alternatief.\n- Bij een sport of topsportdoel: sportspecifieke kracht, explosiviteit en blessurepreventie.\n- Beschrijf progressie (bijvoorbeeld gewicht of herhalingen per week opbouwen) en geef enkele praktische tips.",
    "user": "Intake van de klant:\n- Gewenste schema's: Trainingsschema, Voedingsschema\n- Doel: Afvallen\n- Geslacht: Vrouw\n- Leeftijd: 32 jaar\n- Lengte: 170 cm\n- Gewicht: 72.5 kg\n- Dagelijkse activiteit: Vooral zittend\n- Trainingservaring: Beginner\n- Trainingen per week: 3×\n- Duur per training: 60 minuten\n- Trainingslocatie: Sportschool\n\nMaak nu het trainingsschema."
  }
}
JSON;

    public function test_prompt_bevat_allergieen_en_richtwaarden_geen_persoonsgegevens(): void
    {
        $base = IntakeTest::base();
        ['system' => $system, 'user' => $user] = Prompt::buildPlanPrompt('voeding', [...$base, 'medical' => 'Hoge bloeddruk'], 'Minder koolhydraten in de avond');
        $this->assertMatchesRegularExpression('/Noten, Lactose/', $user);
        $this->assertMatchesRegularExpression('/Doel energie: \\d+ kcal/', $user);
        $this->assertMatchesRegularExpression('/Hoge bloeddruk/', $user);
        $this->assertMatchesRegularExpression('/Minder koolhydraten in de avond/', $user);
        $this->assertMatchesRegularExpression('/harde eisen/', $system);
        $this->assertDoesNotMatchRegularExpression('/Trainingslocatie/', $user, 'trainingsvragen niet in voedingsprompt');
        $training = Prompt::buildPlanPrompt('training', $base);
        $this->assertMatchesRegularExpression('/Trainingen per week: 3×/', $training['user']);
        $this->assertDoesNotMatchRegularExpression('/Allergieën/', $training['user']);
    }

    public function test_prompts_zijn_letterlijk_gelijk_aan_de_typescript_versie(): void
    {
        $expected = json_decode(self::EXPECTED, true);
        $base = IntakeTest::base();
        $this->assertSame($expected['voeding'], Prompt::buildPlanPrompt('voeding', [...$base, 'medical' => 'Hoge bloeddruk'], 'Minder koolhydraten in de avond', 2026));
        $this->assertSame($expected['training'], Prompt::buildPlanPrompt('training', $base, null, 2026));
    }

    public function test_lege_instructie_wordt_overgeslagen(): void
    {
        $base = IntakeTest::base();
        $this->assertSame(Prompt::buildPlanPrompt('training', $base, null, 2026), Prompt::buildPlanPrompt('training', $base, "  \n ", 2026));
        $this->assertStringContainsString("Aanvullende instructie van Steyn (heeft voorrang):\nFocus op benen\n\nMaak nu het trainingsschema.", Prompt::buildPlanPrompt('training', $base, '  Focus op benen ', 2026)['user']);
    }
}
