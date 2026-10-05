<?php

declare(strict_types=1);

namespace App\Support\Plans;

use App\Support\Intake;
use App\Support\Js;

/**
 * Prompts voor het AI-concept. Steyn controleert elk concept voordat de klant het ziet;
 * de prompt vraagt daarom om een volledig, veilig en direct bruikbaar voorstel.
 *
 * Een klant die Mijn omgeving in het Engels gebruikt (users.locale = 'en') krijgt een Engelse prompt, zodat de AI
 * het hele schema in het Engels schrijft. De intake (en een instructie van Steyn) blijft Nederlands; dat staat in
 * de prompt. De Nederlandse prompt is ongewijzigd (zie tests/Unit/PromptTest.php).
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

    private const SHARED_EN = <<<'TXT'
        You are drafting a plan for a client of SteynPT, the personal training business of Steyn van Leeuwen in Amsterdam. Steyn is a personal trainer and orthomolecular nutritional therapist. He checks and refines every draft before the client gets to see it.

        This client uses SteynPT in English. Write the whole plan in English (British spelling): every text field, including the title, summary, day names, focus, warm-up, exercise names, notes, meal names, times, meal options, ingredients, water target, things to avoid, progression and tips. The client's intake and any instruction from Steyn are written in Dutch, and the field descriptions in the output schema are in Dutch too: use them as information only and do not copy Dutch words into the plan (for example "Day 1 – Upper body", "Breakfast", "After training", "2-2.5 litres per day"). Use metric units.

        Address the client directly as "you": personal, positive and concrete. SteynPT stands for a healthy, achievable lifestyle: no crash diets, no extreme methods and no promises you can't keep.

        Safety comes first:
        - Take injuries, limitations and medical considerations into account. When in doubt, choose the safer option and mention in the text that Steyn will discuss this point with the client.
        - Do not make diagnoses or medical claims. For medical complaints, refer the client to their GP.
        TXT;

    private const TRAINING_EN = self::SHARED_EN."\n\n".<<<'TXT'
        Create a training plan:
        - Exactly as many training days as the client trains per week, fitting within the stated session length (including warm-up and cool-down).
        - Only exercises that suit the location and the available equipment.
        - Matched to experience: beginners get clear, basic exercises with straightforward technique tips; advanced clients get more volume and variety.
        - For injuries: avoid exercises that load that area and give a safe alternative in the notes.
        - For a sport or elite sport goal: sport-specific strength, explosiveness and injury prevention.
        - Describe progression (for example building up weight or reps each week) and give a few practical tips.
        TXT;

    private const NUTRITION_EN = self::SHARED_EN."\n\n".<<<'TXT'
        Create a nutrition plan:
        - Allergies, intolerances and eating style are strict requirements. Do not use any ingredient that conflicts with them, not even as an option or garnish. List them under "avoid", together with a tip to check labels.
        - Use the calculated targets for energy and macros as the targets. Only deviate if the intake gives a clear reason to, and then explain it in the summary.
        - Create exactly as many meals as the client states, with 2 or 3 options per meal including amounts, so there is a choice. Take training into account (for example a meal after training).
        - Use products that are available in Dutch supermarkets and dishes that are easy to prepare.
        - Kcal and protein per option are realistic estimates; the options for each meal are close to each other.
        - Avoid foods the client doesn't like.
        TXT;

    /**
     * @param  'training'|'voeding'  $type
     * @param  array<string, mixed>  $intake  gevalideerde intake
     * @param  int|null  $year  huidig jaar (standaard: nu), voor leeftijd en richtwaarden
     * @param  'nl'|'en'  $language  taal van het schema: de taal van de klant (users.locale)
     * @return array{system: string, user: string}
     */
    public static function buildPlanPrompt(string $type, array $intake, ?string $instruction = null, ?int $year = null, string $language = 'nl'): array
    {
        if ($language === 'en') {
            return self::buildEnglishPlanPrompt($type, $intake, $instruction, $year);
        }

        $summary = self::intakeLines($type, $intake, $year);

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

    /**
     * Zelfde opbouw in het Engels. De regels van de intake houden hun Nederlandse labels en waarden (de AI begrijpt
     * ze); de prompt vraagt om een volledig Engels schema.
     *
     * @param  'training'|'voeding'  $type
     * @param  array<string, mixed>  $intake  gevalideerde intake
     * @return array{system: string, user: string}
     */
    private static function buildEnglishPlanPrompt(string $type, array $intake, ?string $instruction, ?int $year): array
    {
        $summary = self::intakeLines($type, $intake, $year);

        $parts = ["Client intake (in Dutch):\n{$summary}"];

        if ($type === 'voeding') {
            $t = Intake::estimateTargets($intake, $year);
            $parts[] = "Calculated targets (Mifflin-St Jeor with activity factor):\n- Resting metabolic rate: {$t['bmr']} kcal\n- Maintenance: {$t['maintenance']} kcal\n- Target energy: {$t['calories']} kcal\n- Protein: {$t['protein']} g\n- Carbohydrates: {$t['carbs']} g\n- Fat: {$t['fat']} g";
            $parts[] = 'The client trains '.Js::numberToString($intake['trainingDays']).'× per week, '.Js::numberToString($intake['sessionMinutes']).' minutes per session.';
        }

        $instruction = Js::trim($instruction ?? '');
        if ($instruction !== '') {
            $parts[] = "Additional instruction from Steyn (takes precedence; may be written in Dutch):\n{$instruction}";
        }

        $parts[] = ($type === 'training' ? 'Now create the training plan.' : 'Now create the nutrition plan.').' Write all of it in English.';

        return ['system' => $type === 'training' ? self::TRAINING_EN : self::NUTRITION_EN, 'user' => implode("\n\n", $parts)];
    }

    /** De intake als regels "- Label: waarde": algemeen plus de vragen bij dit schematype. */
    private static function intakeLines(string $type, array $intake, ?int $year): string
    {
        $rows = array_filter(
            Intake::intakeSummary($intake, $year),
            fn (array $r) => $r['section'] === 'algemeen' || ($type === 'training' ? $r['section'] === 'training' : $r['section'] === 'voeding'),
        );

        return implode("\n", array_map(fn (array $r) => "- {$r['label']}: {$r['value']}", $rows));
    }
}
