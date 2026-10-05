import { z } from "zod";
import { GOALS } from "./site";

// Intakevragen voor het maken van een trainings- en/of voedingsschema.
// Gedeeld door het formulier, de server-actie, de AI-prompt en het beheerscherm.

export const SEXES = [
  { id: "man", label: "Man" },
  { id: "vrouw", label: "Vrouw" },
  { id: "anders", label: "Anders / zeg ik liever niet" },
] as const;

export const EXPERIENCE = [
  { id: "beginner", label: "Beginner", hint: "Minder dan een half jaar structureel trainen" },
  { id: "gemiddeld", label: "Gemiddeld", hint: "Een half jaar tot twee jaar" },
  { id: "gevorderd", label: "Gevorderd", hint: "Meer dan twee jaar; je kent de basisoefeningen goed" },
] as const;

export const SESSION_MINUTES = [30, 45, 60, 75, 90] as const;

export const LOCATIONS = [
  { id: "sportschool", label: "Sportschool" },
  { id: "thuis-materiaal", label: "Thuis, met wat materiaal" },
  { id: "thuis-geen", label: "Thuis, zonder materiaal" },
  { id: "buiten", label: "Buiten" },
] as const;

export const ACTIVITY_LEVELS = [
  { id: "zittend", label: "Vooral zittend", hint: "Kantoorwerk, weinig wandelen", factor: 1.2 },
  { id: "licht", label: "Licht actief", hint: "Dagelijks wat wandelen of fietsen", factor: 1.375 },
  { id: "actief", label: "Actief", hint: "Veel op de been of fysiek werk", factor: 1.55 },
  { id: "zeer-actief", label: "Zeer actief", hint: "Zwaar fysiek werk", factor: 1.725 },
] as const;

export const DIETS = [
  { id: "alles", label: "Ik eet alles" },
  { id: "flexitarisch", label: "Flexitarisch" },
  { id: "pescotarisch", label: "Pescotarisch (wel vis, geen vlees)" },
  { id: "vegetarisch", label: "Vegetarisch" },
  { id: "veganistisch", label: "Veganistisch" },
  { id: "halal", label: "Halal" },
] as const;

// De 14 wettelijke allergenen (EU) plus lactose-intolerantie.
export const ALLERGIES = [
  { id: "gluten", label: "Gluten" },
  { id: "melk", label: "Melk (koemelkeiwit)" },
  { id: "lactose", label: "Lactose" },
  { id: "ei", label: "Ei" },
  { id: "pinda", label: "Pinda" },
  { id: "noten", label: "Noten" },
  { id: "soja", label: "Soja" },
  { id: "vis", label: "Vis" },
  { id: "schaaldieren", label: "Schaaldieren" },
  { id: "weekdieren", label: "Weekdieren" },
  { id: "selderij", label: "Selderij" },
  { id: "mosterd", label: "Mosterd" },
  { id: "sesam", label: "Sesam" },
  { id: "lupine", label: "Lupine" },
  { id: "sulfiet", label: "Sulfiet" },
] as const;

export const PLAN_WANTS = [
  { id: "training", label: "Trainingsschema" },
  { id: "voeding", label: "Voedingsschema" },
] as const;

const ids = <T extends readonly { id: string }[]>(list: T) => list.map((i) => i.id) as [T[number]["id"], ...T[number]["id"][]];

const optionalText = (max: number) =>
  z
    .string("Controleer dit veld")
    .trim()
    .max(max, `Maximaal ${max} tekens`)
    .optional()
    .transform((v) => v || undefined);

const decimal = (message: string) =>
  z.preprocess((v) => (typeof v === "string" ? v.replace(",", ".").trim() : v), z.coerce.number({ error: message }));

const thisYear = new Date().getFullYear();

export const intakeSchema = z.object({
  wants: z.array(z.enum(ids(PLAN_WANTS), "Maak een geldige keuze"), "Maak een geldige keuze").min(1, "Kies minimaal één schema"),
  goal: z.enum(GOALS.map((g) => g.id) as [string, ...string[]], "Kies je belangrijkste doel"),
  goalDetails: optionalText(600),
  sex: z.enum(ids(SEXES), "Maak een keuze"),
  birthYear: z.coerce
    .number({ error: "Vul je geboortejaar in" })
    .int("Vul je geboortejaar in")
    .min(thisYear - 90, "Vul een geldig geboortejaar in")
    .max(thisYear - 14, "Je moet minimaal 14 jaar zijn"),
  heightCm: z.coerce.number({ error: "Vul je lengte in" }).int("Vul je lengte in hele centimeters in").min(120, "Vul je lengte in centimeters in").max(230, "Vul je lengte in centimeters in"),
  weightKg: decimal("Vul je gewicht in").pipe(z.number().min(35, "Vul een geldig gewicht in").max(300, "Vul een geldig gewicht in")),
  targetWeightKg: z
    .preprocess((v) => (v === "" || v === undefined ? undefined : v), decimal("Vul een geldig streefgewicht in").pipe(z.number().min(35, "Vul een geldig streefgewicht in").max(300, "Vul een geldig streefgewicht in")).optional()),
  experience: z.enum(ids(EXPERIENCE), "Kies je ervaring"),
  trainingDays: z.coerce.number({ error: "Kies hoe vaak je per week wilt trainen" }).int("Kies hoe vaak je per week wilt trainen").min(1, "Kies hoe vaak je per week wilt trainen").max(7, "Kies hoe vaak je per week wilt trainen"),
  sessionMinutes: z.coerce
    .number({ error: "Kies hoe lang een training mag duren" })
    .refine((n) => (SESSION_MINUTES as readonly number[]).includes(n), "Kies hoe lang een training mag duren"),
  location: z.enum(ids(LOCATIONS), "Kies waar je traint"),
  equipment: optionalText(400),
  sport: optionalText(200),
  injuries: optionalText(800),
  activityLevel: z.enum(ids(ACTIVITY_LEVELS), "Kies hoe actief je dagelijks bent"),
  diet: z.enum(ids(DIETS), "Kies je eetstijl"),
  allergies: z.array(z.enum(ids(ALLERGIES), "Maak een geldige keuze"), "Maak een geldige keuze").default([]),
  allergiesOther: optionalText(400),
  dislikes: optionalText(400),
  mealsPerDay: z.coerce.number({ error: "Kies het aantal eetmomenten" }).int().min(2, "Kies het aantal eetmomenten").max(6),
  medical: optionalText(800),
});

export type IntakeData = z.infer<typeof intakeSchema>;

const MULTI_FIELDS = new Set(["wants", "allergies"]);

/** Zet FormData om naar een object dat intakeSchema kan valideren (incl. meerkeuzevelden). */
export function intakeFromFormData(formData: FormData) {
  const raw: Record<string, unknown> = { wants: formData.getAll("wants"), allergies: formData.getAll("allergies") };
  for (const [key, value] of formData.entries()) {
    if (typeof value === "string" && !MULTI_FIELDS.has(key) && !key.startsWith("$") && key !== "consent") raw[key] = value;
  }
  return raw;
}

export function labelOf(list: readonly { id: string; label: string }[], id: string | undefined) {
  return list.find((i) => i.id === id)?.label ?? id ?? "";
}

export type Targets = {
  bmr: number;
  maintenance: number;
  calories: number;
  protein: number;
  carbs: number;
  fat: number;
};

const round = (n: number, step: number) => Math.round(n / step) * step;

/**
 * Richtwaarden voor energie en macro's (Mifflin-St Jeor + activiteitsfactor).
 * Dit is een startpunt voor de AI en voor Steyn, geen medisch advies.
 */
export function estimateTargets(intake: IntakeData, year = new Date().getFullYear()): Targets {
  const age = year - intake.birthYear;
  const sexOffset = intake.sex === "man" ? 5 : intake.sex === "vrouw" ? -161 : -78;
  const bmr = 10 * intake.weightKg + 6.25 * intake.heightCm - 5 * age + sexOffset;
  const activity = ACTIVITY_LEVELS.find((a) => a.id === intake.activityLevel)?.factor ?? 1.375;
  const factor = Math.min(1.9, activity + intake.trainingDays * 0.03);
  const maintenance = bmr * factor;

  const adjustment = intake.goal === "afvallen" ? 0.85 : intake.goal === "spieropbouw" ? 1.1 : 1;
  const floor = Math.max(bmr, intake.sex === "man" ? 1500 : 1200);
  const calories = Math.max(maintenance * adjustment, floor);

  const proteinPerKg = intake.goal === "afvallen" || intake.goal === "spieropbouw" || intake.goal === "prestatie" ? 1.8 : 1.5;
  const protein = Math.min(intake.weightKg * proteinPerKg, 220);
  const fat = (calories * 0.28) / 9;
  const carbs = Math.max(0, (calories - protein * 4 - fat * 9) / 4);

  return {
    bmr: round(bmr, 10),
    maintenance: round(maintenance, 10),
    calories: round(calories, 10),
    protein: round(protein, 5),
    carbs: round(carbs, 5),
    fat: round(fat, 5),
  };
}

/** Leesbare samenvatting van de intake (voor de AI-prompt en het beheerscherm). Bevat geen naam of contactgegevens. */
export function intakeSummary(intake: IntakeData, year = new Date().getFullYear()) {
  const rows: { label: string; value: string; section: "algemeen" | "training" | "voeding" }[] = [
    { section: "algemeen", label: "Gewenste schema's", value: intake.wants.map((w) => labelOf(PLAN_WANTS, w)).join(", ") },
    { section: "algemeen", label: "Doel", value: labelOf(GOALS, intake.goal) },
    { section: "algemeen", label: "Toelichting doel", value: intake.goalDetails ?? "" },
    { section: "algemeen", label: "Geslacht", value: labelOf(SEXES, intake.sex) },
    { section: "algemeen", label: "Leeftijd", value: `${year - intake.birthYear} jaar` },
    { section: "algemeen", label: "Lengte", value: `${intake.heightCm} cm` },
    { section: "algemeen", label: "Gewicht", value: `${intake.weightKg} kg` },
    { section: "algemeen", label: "Streefgewicht", value: intake.targetWeightKg ? `${intake.targetWeightKg} kg` : "" },
    { section: "algemeen", label: "Dagelijkse activiteit", value: labelOf(ACTIVITY_LEVELS, intake.activityLevel) },
    { section: "algemeen", label: "Medische aandachtspunten", value: intake.medical ?? "" },
    { section: "training", label: "Trainingservaring", value: labelOf(EXPERIENCE, intake.experience) },
    { section: "training", label: "Trainingen per week", value: `${intake.trainingDays}×` },
    { section: "training", label: "Duur per training", value: `${intake.sessionMinutes} minuten` },
    { section: "training", label: "Trainingslocatie", value: labelOf(LOCATIONS, intake.location) },
    { section: "training", label: "Beschikbaar materiaal", value: intake.equipment ?? "" },
    { section: "training", label: "Sport", value: intake.sport ?? "" },
    { section: "training", label: "Blessures of beperkingen", value: intake.injuries ?? "" },
    { section: "voeding", label: "Eetstijl", value: labelOf(DIETS, intake.diet) },
    {
      section: "voeding",
      label: "Allergieën en intoleranties",
      value: [...intake.allergies.map((a) => labelOf(ALLERGIES, a)), intake.allergiesOther].filter(Boolean).join(", "),
    },
    { section: "voeding", label: "Lust niet", value: intake.dislikes ?? "" },
    { section: "voeding", label: "Eetmomenten per dag", value: `${intake.mealsPerDay}` },
  ];
  return rows.filter((r) => r.value);
}
