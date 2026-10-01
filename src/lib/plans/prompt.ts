import { estimateTargets, intakeSummary, type IntakeData } from "../intake";
import type { PlanType } from "../db/schema";

// Prompts voor het AI-concept. Steyn controleert elk concept voordat de klant het ziet;
// de prompt vraagt daarom om een volledig, veilig en direct bruikbaar voorstel.

const SHARED = `Je maakt een conceptschema voor een klant van SteynPT, het personal-trainingsbureau van Steyn van Leeuwen in Amsterdam. Steyn is personal trainer en orthomoleculair voedingstherapeut. Hij controleert en verfijnt elk concept voordat de klant het te zien krijgt.

Schrijf in het Nederlands, in de je-vorm, persoonlijk, positief en concreet. SteynPT staat voor een gezonde, haalbare leefstijl: geen crashdiëten, geen extreme methodes en geen beloftes die je niet kunt waarmaken.

Veiligheid gaat voor alles:
- Houd rekening met blessures, beperkingen en medische aandachtspunten. Kies bij twijfel de veiligere variant en benoem in de tekst dat Steyn dit punt met de klant bespreekt.
- Stel geen diagnoses en doe geen medische claims. Verwijs bij medische klachten naar overleg met de huisarts.`;

const TRAINING = `${SHARED}

Maak een trainingsschema:
- Precies zoveel trainingsdagen als de klant per week traint, passend binnen de opgegeven duur (inclusief warming-up en cooling-down).
- Alleen oefeningen die passen bij de locatie en het beschikbare materiaal.
- Afgestemd op ervaring: beginners krijgen overzichtelijke basisoefeningen met duidelijke uitvoeringstips; gevorderden meer volume en variatie.
- Bij blessures: vermijd belastende oefeningen voor dat gebied en geef in de notities een veilig alternatief.
- Bij een sport of topsportdoel: sportspecifieke kracht, explosiviteit en blessurepreventie.
- Beschrijf progressie (bijvoorbeeld gewicht of herhalingen per week opbouwen) en geef enkele praktische tips.`;

const NUTRITION = `${SHARED}

Maak een voedingsschema:
- Allergieën, intoleranties en eetstijl zijn harde eisen. Gebruik geen enkel ingrediënt dat daarmee botst, ook niet als optie of garnering. Vermeld ze onder "avoid", samen met een tip om etiketten te controleren.
- Neem de berekende richtwaarden voor energie en macro's over als targets. Wijk alleen af als de intake daar een duidelijke reden voor geeft en leg dat dan uit in de samenvatting.
- Maak precies zoveel eetmomenten als de klant opgeeft, met per eetmoment 2 of 3 opties met hoeveelheden, zodat er keuze is. Houd rekening met de training (bijvoorbeeld een eetmoment na het trainen).
- Gebruik producten die in Nederlandse supermarkten verkrijgbaar zijn en gerechten die makkelijk te bereiden zijn.
- Kcal en eiwit per optie zijn realistische schattingen; de opties per eetmoment liggen dicht bij elkaar.
- Vermijd producten die de klant niet lust.`;

export function buildPlanPrompt(type: PlanType, intake: IntakeData, instruction?: string | null) {
  const summary = intakeSummary(intake)
    .filter((r) => r.section === "algemeen" || (type === "training" ? r.section === "training" : r.section === "voeding"))
    .map((r) => `- ${r.label}: ${r.value}`)
    .join("\n");

  const parts = [`Intake van de klant:\n${summary}`];

  if (type === "voeding") {
    const t = estimateTargets(intake);
    parts.push(
      `Berekende richtwaarden (Mifflin-St Jeor met activiteitsfactor):\n- Rustmetabolisme: ${t.bmr} kcal\n- Onderhoud: ${t.maintenance} kcal\n- Doel energie: ${t.calories} kcal\n- Eiwit: ${t.protein} g\n- Koolhydraten: ${t.carbs} g\n- Vet: ${t.fat} g`,
    );
    parts.push(`De klant traint ${intake.trainingDays}× per week, ${intake.sessionMinutes} minuten.`);
  }

  if (instruction?.trim()) {
    parts.push(`Aanvullende instructie van Steyn (heeft voorrang):\n${instruction.trim()}`);
  }

  parts.push(type === "training" ? "Maak nu het trainingsschema." : "Maak nu het voedingsschema.");

  return { system: type === "training" ? TRAINING : NUTRITION, user: parts.join("\n\n") };
}
