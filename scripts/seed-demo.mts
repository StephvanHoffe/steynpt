// Vult de database met voorbeeldgegevens voor de demoversie (DEMO_MODE=1).
// Draait bij elke start van de demo: staat het demo-account er al, dan gebeurt er niets.
// Met --reset wordt eerst alles gewist. Datums zijn relatief aan vandaag, zodat de demo altijd actueel oogt.
//
//   DEMO_MODE=1 npx tsx scripts/seed-demo.mts [--reset]

import { randomBytes, randomUUID } from "node:crypto";
import { mkdirSync } from "node:fs";
import { createClient } from "@libsql/client";
import bcrypt from "bcryptjs";
import { eq } from "drizzle-orm";
import { drizzle } from "drizzle-orm/libsql";
import { addDays, getAppointmentType, weekdayOf, zonedParts, zonedTimeToUtc } from "../src/lib/agenda";
import * as s from "../src/lib/db/schema";
import { DEMO_ACCOUNTS } from "../src/lib/demo";
import { estimateTargets, intakeSchema, type IntakeData } from "../src/lib/intake";
import { defaultRenewOn } from "../src/lib/plans/pipeline";
import { nutritionPlanSchema, trainingPlanSchema, type NutritionPlan, type TrainingPlan } from "../src/lib/plans/schema";
import { makeReferralCode } from "../src/lib/referral-program";
import { isoWeekKey } from "../src/lib/weeks";

// Nooit per ongeluk voorbeeldaccounts (waaronder een beheerder) in de echte database zetten.
if (process.env.DEMO_MODE !== "1") {
  console.error("Alleen voor de demoversie: zet DEMO_MODE=1.");
  process.exit(1);
}

const url = process.env.DATABASE_URL ?? "file:./data/steynpt.db";
if (url.startsWith("file:")) mkdirSync("data", { recursive: true });
const client = createClient({ url, authToken: process.env.DATABASE_AUTH_TOKEN });
const db = drizzle(client, { schema: s });

const [klant, steyn] = [DEMO_ACCOUNTS[0], DEMO_ACCOUNTS[1]];

if (process.argv.includes("--reset")) {
  for (const table of [s.sessions, s.loginChallenges, s.recoveryCodes, s.checkIns, s.appointments, s.measurements, s.plans, s.intakes, s.contactRequests, s.availability, s.blockedPeriods, s.settings]) {
    await db.delete(table);
  }
  await db.update(s.users).set({ referredById: null });
  await db.delete(s.users);
  console.log("Database leeggemaakt.");
}

const existing = await db.select({ id: s.users.id }).from(s.users).where(eq(s.users.email, steyn.email)).limit(1);
if (existing.length) {
  console.log("Demo-gegevens staan er al.");
  client.close();
  process.exit(0);
}

// ---------------------------------------------------------------------------
// Hulpjes

const now = new Date();
const today = zonedParts(now).day;
const daysAgo = (n: number) => new Date(now.getTime() - n * 864e5);
/** Eerstvolgende dag met deze weekdag (1 = maandag), minimaal `minAhead` dagen vooruit. */
const nextWeekday = (weekday: number, minAhead = 1) => {
  for (let i = minAhead; i < minAhead + 7; i++) if (weekdayOf(addDays(today, i)) === weekday) return addDays(today, i);
  throw new Error("onbereikbaar");
};
// Willekeurig wachtwoord: de demo-accounts zijn alleen te gebruiken via de inlogknoppen.
const password = () => bcrypt.hash(randomBytes(18).toString("base64url"), 10);

async function user(values: Omit<typeof s.users.$inferInsert, "id" | "passwordHash" | "referralCode">) {
  const id = randomUUID();
  await db.insert(s.users).values({ id, passwordHash: await password(), referralCode: makeReferralCode(values.firstName), passwordChangedAt: now, ...values });
  return id;
}

function appointment(userId: string, typeId: string, location: string, day: string, time: string, note?: string) {
  const type = getAppointmentType(typeId)!;
  const startsAt = zonedTimeToUtc(day, time);
  return { userId, type: type.id, location, startsAt, endsAt: new Date(startsAt.getTime() + type.minutes * 60_000), note: note ?? null };
}

// ---------------------------------------------------------------------------
// Leden

await user({
  email: steyn.email,
  firstName: "Steyn",
  lastName: "van Leeuwen",
  phone: "06 1111 2222",
  goal: "fitter",
  role: "admin",
  createdAt: daysAgo(160),
});

const lisaId = await user({
  email: klant.email,
  firstName: "Lisa",
  lastName: "Jansen",
  phone: "06 2345 6789",
  goal: "afvallen",
  plan: "online-pro",
  coachingStatus: "actief",
  coachNote: "Sterke maand, Lisa! Je taille is 6 cm kleiner sinds de start. Deze week voeren we het gewicht bij de squat op met 2,5 kg.",
  createdAt: daysAgo(122),
});

const sanneId = await user({
  email: "sanne.devries@example.com",
  firstName: "Sanne",
  lastName: "de Vries",
  phone: "06 3456 7890",
  goal: "spieropbouw",
  plan: "online-pro",
  coachingStatus: "actief",
  coachNote: "Welkom Sanne! Je eerste schema volgt na de intake.",
  referredById: lisaId,
  createdAt: daysAgo(12),
});

const noorId = await user({
  email: "noor.visser@example.com",
  firstName: "Noor",
  lastName: "Visser",
  phone: "06 7890 1234",
  goal: "prestatie",
  plan: "online-performance",
  coachingStatus: "actief",
  referredById: lisaId,
  referralRewardAt: daysAgo(30),
  createdAt: daysAgo(64),
});

const markId = await user({
  email: "mark.bakker@example.com",
  firstName: "Mark",
  lastName: "Bakker",
  phone: "06 4567 8901",
  goal: "prestatie",
  createdAt: daysAgo(3),
});

const tomId = await user({
  email: "tom.degroot@example.com",
  firstName: "Tom",
  lastName: "de Groot",
  phone: "06 5678 9012",
  goal: "spieropbouw",
  plan: "online-start",
  coachingStatus: "aangevraagd",
  createdAt: daysAgo(1),
});

// Meer coachingklanten, zodat het overzicht van de schema's alle fases laat zien.
const coachingClient = (firstName: string, lastName: string, plan: string, goal: string, createdDaysAgo: number, coachingStatus: "actief" | "gepauzeerd" = "actief") =>
  user({
    email: `${firstName}.${lastName.replace(/\s/g, "")}@example.com`.toLowerCase(),
    firstName,
    lastName,
    goal,
    plan,
    coachingStatus,
    createdAt: daysAgo(createdDaysAgo),
  });
const fleurId = await coachingClient("Fleur", "Hendriks", "online-pro", "afvallen", 4);
const daanId = await coachingClient("Daan", "Mulder", "online-start", "spieropbouw", 45);
const elineId = await coachingClient("Eline", "Kok", "online-pro", "fitter", 58);
const jorisId = await coachingClient("Joris", "Peters", "online-pro", "spieropbouw", 30);
const milaId = await coachingClient("Mila", "Bos", "online-performance", "prestatie", 20);
const semId = await coachingClient("Sem", "Vermeulen", "online-start", "fitter", 16);
const irisId = await coachingClient("Iris", "Dekker", "online-start", "leefstijl", 9);
const rubenId = await coachingClient("Ruben", "van Dijk", "online-pro", "herstel", 80, "gepauzeerd");

// ---------------------------------------------------------------------------
// Intakes

const lisaIntake: IntakeData = intakeSchema.parse({
  wants: ["training", "voeding"],
  goal: "afvallen",
  goalDetails: "Zes kilo afvallen en me weer fit voelen. Ik werk veel achter een bureau.",
  sex: "vrouw",
  birthYear: now.getFullYear() - 35,
  heightCm: 170,
  weightKg: "78,9",
  targetWeightKg: "72",
  experience: "gemiddeld",
  trainingDays: 3,
  sessionMinutes: 60,
  location: "sportschool",
  activityLevel: "zittend",
  diet: "alles",
  allergies: [],
  dislikes: "Spruitjes",
  mealsPerDay: 4,
});

const tomIntake: IntakeData = intakeSchema.parse({
  wants: ["training", "voeding"],
  goal: "spieropbouw",
  goalDetails: "Vijf kilo spiermassa erbij en sterker worden.",
  sex: "man",
  birthYear: now.getFullYear() - 30,
  heightCm: 184,
  weightKg: "79",
  experience: "beginner",
  trainingDays: 4,
  sessionMinutes: 60,
  location: "sportschool",
  injuries: "Oude enkelblessure rechts, soms instabiel bij springen.",
  activityLevel: "actief",
  diet: "vegetarisch",
  allergies: ["noten"],
  mealsPerDay: 4,
});

/** Eenvoudige intake voor de extra klanten. */
const quickIntake = (wants: ("training" | "voeding")[], goal: string, sex: "man" | "vrouw", extra: Record<string, unknown> = {}): IntakeData =>
  intakeSchema.parse({
    wants,
    goal,
    sex,
    birthYear: now.getFullYear() - 31,
    heightCm: sex === "man" ? 183 : 169,
    weightKg: sex === "man" ? "81" : "65",
    experience: "gemiddeld",
    trainingDays: 3,
    sessionMinutes: 60,
    location: "sportschool",
    activityLevel: "licht",
    diet: "alles",
    allergies: [],
    mealsPerDay: 4,
    ...extra,
  });

await db.insert(s.intakes).values([
  { userId: lisaId, data: lisaIntake, createdAt: daysAgo(120), updatedAt: daysAgo(120) },
  { userId: tomId, data: tomIntake, createdAt: daysAgo(1), updatedAt: daysAgo(1) },
  { userId: noorId, data: quickIntake(["training", "voeding"], "prestatie", "vrouw", { experience: "gevorderd", trainingDays: 5 }), createdAt: daysAgo(62), updatedAt: daysAgo(62) },
  { userId: fleurId, data: quickIntake(["training", "voeding"], "afvallen", "vrouw", { experience: "beginner", allergies: ["lactose"] }), createdAt: daysAgo(2), updatedAt: daysAgo(2) },
  { userId: daanId, data: quickIntake(["training"], "spieropbouw", "man", { trainingDays: 4 }), createdAt: daysAgo(44), updatedAt: daysAgo(44) },
  { userId: elineId, data: quickIntake(["training", "voeding"], "fitter", "vrouw"), createdAt: daysAgo(57), updatedAt: daysAgo(57) },
  { userId: jorisId, data: quickIntake(["training", "voeding"], "spieropbouw", "man", { trainingDays: 4, diet: "flexitarisch" }), createdAt: daysAgo(29), updatedAt: daysAgo(29) },
  { userId: milaId, data: quickIntake(["training", "voeding"], "prestatie", "vrouw", { experience: "gevorderd", trainingDays: 5 }), createdAt: daysAgo(19), updatedAt: daysAgo(19) },
  { userId: semId, data: quickIntake(["training"], "fitter", "man"), createdAt: daysAgo(15), updatedAt: daysAgo(15) },
  { userId: irisId, data: quickIntake(["training"], "leefstijl", "vrouw", { location: "thuis-materiaal" }), createdAt: daysAgo(8), updatedAt: daysAgo(8) },
  { userId: rubenId, data: quickIntake(["training", "voeding"], "herstel", "man", { injuries: "Herstellende van een knieoperatie." }), createdAt: daysAgo(79), updatedAt: daysAgo(79) },
]);

// ---------------------------------------------------------------------------
// Schema's

const lisaTraining: TrainingPlan = trainingPlanSchema.parse({
  title: "Sterker en lichter: blok 2",
  summary:
    "Drie full-body trainingen per week met de nadruk op de grote basisoefeningen. Zo verbrand je meer en blijft je spiermassa behouden terwijl je afvalt. Na zes weken evalueren we samen.",
  durationWeeks: 6,
  daysPerWeek: 3,
  days: [
    {
      name: "Dag 1 – Onderlichaam",
      focus: "Squat en heupen",
      warmup: "5 minuten roeien, daarna heup- en enkelmobiliteit en 2 lichte sets goblet squat.",
      exercises: [
        { name: "Back squat", sets: "4", reps: "8", rest: "2 min", notes: "Diep zakken met een neutrale rug." },
        { name: "Roemeense deadlift", sets: "3", reps: "10", rest: "90 sec", notes: "Beweeg vanuit de heupen." },
        { name: "Walking lunges", sets: "3", reps: "10 per been", rest: "60 sec", notes: "" },
        { name: "Hip thrust", sets: "3", reps: "12", rest: "60 sec", notes: "" },
        { name: "Plank", sets: "3", reps: "40 sec", rest: "45 sec", notes: "" },
      ],
      cooldown: "5 minuten rustig fietsen en rekken van heupbuigers en hamstrings.",
    },
    {
      name: "Dag 2 – Bovenlichaam",
      focus: "Duwen en trekken",
      warmup: "5 minuten crosstrainer, schoudermobiliteit met elastiek.",
      exercises: [
        { name: "Dumbbell bench press", sets: "4", reps: "8-10", rest: "90 sec", notes: "" },
        { name: "Lat pulldown", sets: "3", reps: "10-12", rest: "90 sec", notes: "Schouders laag houden." },
        { name: "Zittend roeien aan de kabel", sets: "3", reps: "12", rest: "60 sec", notes: "" },
        { name: "Schouderpress met dumbbells", sets: "3", reps: "10", rest: "60 sec", notes: "" },
        { name: "Face pulls", sets: "3", reps: "15", rest: "45 sec", notes: "" },
      ],
      cooldown: "Rekken van borst en schouders, 5 minuten.",
    },
    {
      name: "Dag 3 – Full body en conditie",
      focus: "Deadlift en intervallen",
      warmup: "5 minuten roeien, heupscharnier oefenen met een stok.",
      exercises: [
        { name: "Trap bar deadlift", sets: "4", reps: "6", rest: "2 min", notes: "Techniek gaat voor gewicht." },
        { name: "Push-ups", sets: "3", reps: "max - 2", rest: "60 sec", notes: "" },
        { name: "Kettlebell swings", sets: "4", reps: "15", rest: "45 sec", notes: "" },
        { name: "Farmer's walk", sets: "3", reps: "30 meter", rest: "60 sec", notes: "" },
        { name: "Roei-intervallen", sets: "6", reps: "30 sec hard / 60 sec rustig", rest: "-", notes: "" },
      ],
      cooldown: "Rustig uitroeien en rekken, 5 minuten.",
    },
  ],
  progression: "Haal je alle herhalingen met goede techniek? Verhoog dan de volgende week het gewicht met 2,5 kg bij de basisoefeningen.",
  tips: ["Noteer je gewichten na elke training.", "Probeer minimaal 8.000 stappen per dag te zetten.", "Slaap 7 tot 8 uur voor een goed herstel."],
});

const lisaTargets = estimateTargets(lisaIntake);
const lisaNutrition: NutritionPlan = nutritionPlanSchema.parse({
  title: "Voedingsplan afvallen",
  summary:
    "Een licht calorietekort met voldoende eiwit, zodat je afvalt zonder spiermassa te verliezen. Kies per eetmoment de optie die past bij je dag.",
  targets: { calories: lisaTargets.calories, protein: lisaTargets.protein, carbs: lisaTargets.carbs, fat: lisaTargets.fat, water: "2-2,5 liter per dag" },
  meals: [
    {
      name: "Ontbijt",
      time: "07:30",
      options: [
        { title: "Overnight oats", ingredients: "40 g havermout, 200 g magere kwark, blauwe bessen, kaneel", kcal: 380, protein: 30 },
        { title: "Omelet met volkoren brood", ingredients: "2 eieren, spinazie, 1 volkoren boterham", kcal: 350, protein: 24 },
      ],
    },
    {
      name: "Lunch",
      time: "12:30",
      options: [
        { title: "Wrap met kip", ingredients: "Volkoren wrap, 100 g kipfilet, sla, tomaat, hummus", kcal: 450, protein: 35 },
        { title: "Linzensalade", ingredients: "150 g linzen, feta, komkommer, paprika, olijfolie", kcal: 430, protein: 26 },
      ],
    },
    {
      name: "Tussendoor",
      time: "15:30",
      options: [{ title: "Skyr met fruit", ingredients: "150 g skyr en een appel", kcal: 180, protein: 17 }],
    },
    {
      name: "Avondeten",
      time: "18:30",
      options: [
        { title: "Zalm met zoete aardappel", ingredients: "125 g zalm, 150 g zoete aardappel, broccoli", kcal: 520, protein: 34 },
        { title: "Kipcurry", ingredients: "125 g kipfilet, 60 g zilvervliesrijst, wokgroenten, light kokosmelk", kcal: 540, protein: 38 },
      ],
    },
  ],
  avoid: ["Spruitjes (lust je niet)", "Suikerrijke frisdrank"],
  tips: ["Begin elke maaltijd met groente.", "Bereid je lunch de avond ervoor voor.", "Drink een glas water bij elk eetmoment."],
});

const tomTargets = estimateTargets(tomIntake);
// Concept met bewuste fouten, zodat de automatische allergie- en eetstijlcontrole iets laat zien.
const tomNutrition: NutritionPlan = nutritionPlanSchema.parse({
  title: "Voedingsplan spieropbouw",
  summary: "Een licht calorie-overschot met veel eiwit uit plantaardige bronnen en zuivel, verdeeld over vier eetmomenten.",
  targets: { calories: tomTargets.calories, protein: tomTargets.protein, carbs: tomTargets.carbs, fat: tomTargets.fat, water: "2,5-3 liter per dag" },
  meals: [
    {
      name: "Ontbijt",
      time: "07:30",
      options: [{ title: "Havermout met noten", ingredients: "80 g havermout, 250 ml melk, 20 g walnoten, banaan", kcal: 650, protein: 25 }],
    },
    {
      name: "Lunch",
      time: "12:30",
      options: [{ title: "Bowl met kip", ingredients: "100 g quinoa, 120 g kipfilet, avocado, edamame", kcal: 750, protein: 45 }],
    },
    {
      name: "Na de training",
      time: "Na het trainen",
      options: [{ title: "Herstelshake", ingredients: "30 g wei-eiwit, banaan, 300 ml melk", kcal: 420, protein: 38 }],
    },
    {
      name: "Avondeten",
      time: "18:30",
      options: [{ title: "Tofu-roerbak", ingredients: "200 g tofu, 90 g volkoren noedels, paksoi, sojasaus", kcal: 780, protein: 42 }],
    },
  ],
  avoid: ["Noten (allergie)"],
  tips: ["Eet binnen twee uur na de training een eiwitrijke maaltijd."],
});

const tomTraining: TrainingPlan = trainingPlanSchema.parse({
  title: "Basis spieropbouw",
  summary: "Vier trainingen per week in een boven-/onderverdeling. We bouwen rustig op en leren eerst de techniek van de basisoefeningen.",
  durationWeeks: 8,
  daysPerWeek: 4,
  days: [
    {
      name: "Dag 1 – Bovenlichaam",
      focus: "Duwen",
      warmup: "5 minuten roeien, schoudermobiliteit.",
      exercises: [
        { name: "Bench press", sets: "4", reps: "8", rest: "2 min", notes: "" },
        { name: "Dumbbell row", sets: "3", reps: "10 per kant", rest: "90 sec", notes: "" },
        { name: "Overhead press", sets: "3", reps: "8-10", rest: "90 sec", notes: "" },
      ],
      cooldown: "Rekken, 5 minuten.",
    },
    {
      name: "Dag 2 – Onderlichaam",
      focus: "Squat",
      warmup: "Fietsen en enkelmobiliteit.",
      exercises: [
        { name: "Goblet squat", sets: "4", reps: "10", rest: "90 sec", notes: "Let op de rechterenkel." },
        { name: "Box jumps", sets: "3", reps: "6", rest: "90 sec", notes: "" },
        { name: "Leg curl", sets: "3", reps: "12", rest: "60 sec", notes: "" },
      ],
      cooldown: "Rekken, 5 minuten.",
    },
    {
      name: "Dag 3 – Bovenlichaam",
      focus: "Trekken",
      warmup: "5 minuten roeien.",
      exercises: [
        { name: "Lat pulldown", sets: "4", reps: "10", rest: "90 sec", notes: "" },
        { name: "Incline dumbbell press", sets: "3", reps: "10", rest: "90 sec", notes: "" },
        { name: "Biceps curl", sets: "3", reps: "12", rest: "60 sec", notes: "" },
      ],
      cooldown: "Rekken, 5 minuten.",
    },
    {
      name: "Dag 4 – Onderlichaam",
      focus: "Heupen",
      warmup: "Fietsen en heupmobiliteit.",
      exercises: [
        { name: "Roemeense deadlift", sets: "4", reps: "8", rest: "2 min", notes: "" },
        { name: "Bulgarian split squat", sets: "3", reps: "8 per been", rest: "90 sec", notes: "" },
        { name: "Calf raises", sets: "3", reps: "15", rest: "45 sec", notes: "" },
      ],
      cooldown: "Rekken, 5 minuten.",
    },
  ],
  progression: "Elke week één herhaling erbij; bij het bovenste getal gaat het gewicht omhoog.",
  tips: ["Neem bij twijfel over je enkel contact op met Steyn."],
});

/** Gepubliceerd schema van `ago` dagen geleden; het volgende schema is over `renewIn` dagen nodig (negatief = te laat). */
function published(userId: string, content: TrainingPlan | NutritionPlan, ago: number, renewIn?: number, title?: string) {
  const type = "days" in content ? ("training" as const) : ("voeding" as const);
  const publishedDay = addDays(today, -ago);
  return {
    userId,
    type,
    status: "gepubliceerd" as const,
    content: title ? { ...content, title } : content,
    aiDraft: content,
    source: "ai" as const,
    model: "voorbeeld",
    createdAt: daysAgo(ago + 1),
    updatedAt: daysAgo(ago),
    publishedAt: daysAgo(ago),
    startsOn: publishedDay,
    renewOn: renewIn === undefined ? defaultRenewOn(type, publishedDay, "days" in content ? content.durationWeeks : null) : addDays(today, renewIn),
  };
}

await db.insert(s.plans).values([
  // Lisa: eerder blok 1, nu blok 2; voeding is de komende week aan vernieuwing toe.
  {
    ...published(lisaId, lisaTraining, 64, undefined, "Sterker en lichter: blok 1"),
    status: "vervangen" as const,
    renewOn: addDays(today, -22),
  },
  published(lisaId, lisaTraining, 22),
  published(lisaId, lisaNutrition, 22),
  { userId: tomId, type: "training", status: "concept", content: tomTraining, aiDraft: tomTraining, source: "ai", model: "voorbeeld", createdAt: daysAgo(1), updatedAt: daysAgo(1) },
  { userId: tomId, type: "voeding", status: "concept", content: tomNutrition, aiDraft: tomNutrition, source: "ai", model: "voorbeeld", createdAt: daysAgo(1), updatedAt: daysAgo(1) },
  // Toe aan een nieuw schema
  published(noorId, tomTraining, 45, -3, "Wedstrijdvoorbereiding: opbouw"),
  published(elineId, lisaNutrition, 32, -1, "Voedingsplan meer energie"),
  // Komende week
  published(daanId, tomTraining, 38, 4, "Basis spieropbouw"),
  // Actief
  published(noorId, lisaNutrition, 16, 12, "Voedingsplan wedstrijdperiode"),
  published(elineId, lisaTraining, 10, 32, "Fit in 6 weken"),
  published(jorisId, tomTraining, 26, 30, "Spieropbouw 4 dagen"),
  published(jorisId, tomNutrition, 26, 9, "Voedingsplan spieropbouw"),
  // Ingepland: het volgende voedingsplan van Joris staat al klaar en wordt zichtbaar als het huidige afloopt.
  {
    userId: jorisId,
    type: "voeding" as const,
    status: "gepland" as const,
    content: { ...tomNutrition, title: "Voedingsplan spieropbouw: fase 2" },
    source: "handmatig" as const,
    createdAt: daysAgo(1),
    updatedAt: daysAgo(1),
    startsOn: addDays(today, 9),
    renewOn: addDays(today, 9 + 28),
  },
  published(milaId, tomTraining, 17, 25, "Sprintkracht"),
  published(milaId, lisaNutrition, 17, 11, "Voedingsplan prestatie"),
  published(semId, lisaTraining, 14, 28, "Fit en sterk"),
  published(irisId, lisaTraining, 7, 35, "Thuis trainen"),
  // Gepauzeerd
  published(rubenId, lisaTraining, 70, -28, "Revalidatie knie"),
  published(rubenId, lisaNutrition, 70, -42, "Voedingsplan herstel"),
]);

// ---------------------------------------------------------------------------
// Metingen en check-ins

const measuredOn = (n: number) => zonedTimeToUtc(addDays(today, -n), "12:00");
await db.insert(s.measurements).values([
  { userId: lisaId, measuredAt: measuredOn(119), weight: 78.9, bodyFat: 32.1, muscleMass: 26.8, waist: 88, hip: 104, note: "Startmeting" },
  { userId: lisaId, measuredAt: measuredOn(91), weight: 77.8, bodyFat: 31.2, muscleMass: 27.0, waist: 86.5, hip: 103 },
  { userId: lisaId, measuredAt: measuredOn(63), weight: 76.9, bodyFat: 30.4, muscleMass: 27.1, waist: 85, hip: 102 },
  { userId: lisaId, measuredAt: measuredOn(35), weight: 75.9, bodyFat: 29.5, muscleMass: 27.3, waist: 83.5, hip: 101 },
  { userId: lisaId, measuredAt: measuredOn(7), weight: 75.1, bodyFat: 28.8, muscleMass: 27.5, waist: 82, hip: 100, note: "Mooi resultaat: taille 6 cm kleiner sinds de start." },
  { userId: noorId, measuredAt: measuredOn(60), weight: 64.2, bodyFat: 22.4, muscleMass: 27.9 },
  { userId: noorId, measuredAt: measuredOn(30), weight: 63.8, bodyFat: 21.6, muscleMass: 28.3 },
  { userId: noorId, measuredAt: measuredOn(2), weight: 63.5, bodyFat: 20.9, muscleMass: 28.6 },
  { userId: sanneId, measuredAt: measuredOn(10), weight: 61.4, bodyFat: 26.0, muscleMass: 24.1, note: "Startmeting" },
]);

const lisaCheckIns = [
  { weeksAgo: 0, weight: 75.1, energy: 4, sleep: 3, nutrition: 4, workouts: 3, note: "Goede week. Wel wat korter geslapen door drukte op werk." },
  { weeksAgo: 1, weight: 75.3, energy: 4, sleep: 4, nutrition: 4, workouts: 3, note: "" },
  { weeksAgo: 2, weight: 75.6, energy: 3, sleep: 4, nutrition: 3, workouts: 2, note: "Verjaardag in het weekend." },
  { weeksAgo: 3, weight: 75.9, energy: 4, sleep: 4, nutrition: 4, workouts: 3, note: "" },
  { weeksAgo: 4, weight: 76.2, energy: 5, sleep: 4, nutrition: 5, workouts: 3, note: "Voel me sterker bij de squat." },
  { weeksAgo: 5, weight: 76.6, energy: 3, sleep: 3, nutrition: 4, workouts: 3, note: "" },
];
await db.insert(s.checkIns).values(
  lisaCheckIns.map(({ weeksAgo, note, ...c }) => ({ userId: lisaId, week: isoWeekKey(daysAgo(weeksAgo * 7)), note: note || null, createdAt: daysAgo(weeksAgo * 7), ...c })),
);

// ---------------------------------------------------------------------------
// Agenda: beschikbaarheid, vakantie en afspraken

await db.insert(s.availability).values([
  ...[1, 2, 3, 4, 5].map((weekday) => ({ weekday, startTime: "07:00", endTime: "12:00", location: "gymbase" })),
  { weekday: 1, startTime: "16:00", endTime: "20:00", location: "gymbase" },
  { weekday: 3, startTime: "16:00", endTime: "20:00", location: "gymbase" },
  { weekday: 2, startTime: "13:00", endTime: "16:00", location: "online" },
  { weekday: 4, startTime: "13:00", endTime: "16:00", location: "online" },
  { weekday: 6, startTime: "08:00", endTime: "12:00", location: "gymbase" },
]);

const vacation = nextWeekday(1, 21);
await db.insert(s.blockedPeriods).values({
  startsAt: zonedTimeToUtc(vacation, "00:00"),
  endsAt: zonedTimeToUtc(addDays(vacation, 5), "00:00"),
  reason: "Herfstvakantie",
});

await db.insert(s.appointments).values([
  // afgelopen weken
  { ...appointment(lisaId, "personal-training", "gymbase", addDays(nextWeekday(1), -7), "07:30"), createdAt: daysAgo(14) },
  { ...appointment(lisaId, "meting", "gymbase", addDays(today, -7), "08:00"), createdAt: daysAgo(10) },
  // komende afspraken
  appointment(sanneId, "personal-training", "gymbase", nextWeekday(5), "08:00", "Eerste training, graag uitleg over de basisoefeningen"),
  appointment(lisaId, "personal-training", "gymbase", nextWeekday(1), "07:30", "Graag focus op techniek bij de deadlift"),
  appointment(tomId, "meting", "gymbase", nextWeekday(2), "09:00"),
  appointment(noorId, "online-call", "online", nextWeekday(2), "14:00", "Evaluatie wedstrijdvoorbereiding"),
  appointment(markId, "kennismaking", "gymbase", nextWeekday(3), "16:30", "Ik train voor de marathon van Amsterdam"),
  appointment(lisaId, "online-call", "online", nextWeekday(4), "13:30"),
  appointment(lisaId, "personal-training", "gymbase", nextWeekday(1, 8), "07:30"),
]);

// ---------------------------------------------------------------------------
// Contactaanvragen

await db.insert(s.contactRequests).values([
  {
    name: "Eva Smit",
    email: "eva.smit@example.com",
    phone: "06 6789 0123",
    interest: "ademcoaching",
    message: "Wij zoeken ademcoaching voor ons team van 12 personen. Kan dat op locatie in Amsterdam-Zuid?",
    createdAt: daysAgo(0),
  },
  {
    name: "Jeroen de Wit",
    email: "jeroen.dewit@example.com",
    interest: "personal-training",
    message: "Ik wil graag sterker worden na een rugblessure.",
    handled: true,
    createdAt: daysAgo(6),
  },
]);

console.log("Demo-gegevens aangemaakt.");
client.close();
