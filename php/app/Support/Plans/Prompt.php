<?php

declare(strict_types=1);

namespace App\Support\Plans;

use App\Support\Intake;
use App\Support\Js;

/**
 * Prompts voor het AI-concept. Steyn controleert elk concept voordat de klant het ziet;
 * de prompt vraagt daarom om een volledig, veilig en direct bruikbaar voorstel.
 */
final class Prompt
{
    private const SHARED = <<<'TXT'
        Je maakt een conceptschema voor een klant van SteynPT, het personal-trainingsbureau van Steyn van Leeuwen in Amsterdam. Steyn is personal trainer en orthomoleculair voedingstherapeut. Hij controleert en verfijnt elk concept voordat de klant het te zien krijgt.

        Schrijf in het Nederlands, in de je-vorm, persoonlijk, positief en concreet. SteynPT staat voor een gezonde, haalbare leefstijl: geen crashdiëten, geen extreme methodes en geen beloftes die je niet kunt waarmaken.

        Veiligheid gaat voor alles:
        - Houd rekening met blessures, beperkingen en medische aandachtspunten. Kies bij twijfel de veiligere variant en benoem in de tekst dat Steyn dit punt met de klant bespreekt.
        - Stel geen diagnoses en doe geen medische claims. Verwijs bij medische klachten naar overleg met de huisarts.
        TXT;

    private const TRAINING = self::SHARED."\n\n".<<<'TXT'
        Maak een trainingsschema:
        - Precies zoveel trainingsdagen als de klant per week traint, passend binnen de opgegeven duur (inclusief warming-up en cooling-down).
        - Alleen oefeningen die passen bij de locatie en het beschikbare materiaal.
        - Afgestemd op ervaring: beginners krijgen overzichtelijke basisoefeningen met duidelijke uitvoeringstips; gevorderden meer volume en variatie.
        - Bij blessures: vermijd belastende oefeningen voor dat gebied en geef in de notities een veilig alternatief.
        - Bij een sport of topsportdoel: sportspecifieke kracht, explosiviteit en blessurepreventie.
        - Beschrijf progressie (bijvoorbeeld gewicht of herhalingen per week opbouwen) en geef enkele praktische tips.
        TXT;

    private const NUTRITION = self::SHARED."\n\n".<<<'TXT'
        Maak een voedingsschema:
        - Allergieën, intoleranties en eetstijl zijn harde eisen. Gebruik geen enkel ingrediënt dat daarmee botst, ook niet als optie of garnering. Vermeld ze onder "avoid", samen met een tip om etiketten te controleren.
        - Neem de berekende richtwaarden voor energie en macro's over als targets. Wijk alleen af als de intake daar een duidelijke reden voor geeft en leg dat dan uit in de samenvatting.
        - Maak precies zoveel eetmomenten als de klant opgeeft, met per eetmoment 2 of 3 opties met hoeveelheden, zodat er keuze is. Houd rekening met de training (bijvoorbeeld een eetmoment na het trainen).
        - Gebruik producten die in Nederlandse supermarkten verkrijgbaar zijn en gerechten die makkelijk te bereiden zijn.
        - Kcal en eiwit per optie zijn realistische schattingen; de opties per eetmoment liggen dicht bij elkaar.
        - Vermijd producten die de klant niet lust.
        TXT;

    /**
     * @param  'training'|'voeding'  $type
     * @param  array<string, mixed>  $intake  gevalideerde intake
     * @param  int|null  $year  huidig jaar (standaard: nu), voor leeftijd en richtwaarden
     * @return array{system: string, user: string}
     */
    public static function buildPlanPrompt(string $type, array $intake, ?string $instruction = null, ?int $year = null): array
    {
        $rows = array_filter(
            Intake::intakeSummary($intake, $year),
            fn (array $r) => $r['section'] === 'algemeen' || ($type === 'training' ? $r['section'] === 'training' : $r['section'] === 'voeding'),
        );
        $summary = implode("\n", array_map(fn (array $r) => "- {$r['label']}: {$r['value']}", $rows));

        $parts = ["Intake van de klant:\n{$summary}"];

        if ($type === 'voeding') {
            $t = Intake::estimateTargets($intake, $year);
            $parts[] = "Berekende richtwaarden (Mifflin-St Jeor met activiteitsfactor):\n- Rustmetabolisme: {$t['bmr']} kcal\n- Onderhoud: {$t['maintenance']} kcal\n- Doel energie: {$t['calories']} kcal\n- Eiwit: {$t['protein']} g\n- Koolhydraten: {$t['carbs']} g\n- Vet: {$t['fat']} g";
            $parts[] = 'De klant traint '.Js::numberToString($intake['trainingDays']).'× per week, '.Js::numberToString($intake['sessionMinutes']).' minuten.';
        }

        $instruction = Js::trim($instruction ?? '');
        if ($instruction !== '') {
            $parts[] = "Aanvullende instructie van Steyn (heeft voorrang):\n{$instruction}";
        }

        $parts[] = $type === 'training' ? 'Maak nu het trainingsschema.' : 'Maak nu het voedingsschema.';

        return ['system' => $type === 'training' ? self::TRAINING : self::NUTRITION, 'user' => implode("\n\n", $parts)];
    }
}
