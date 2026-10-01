import { estimateTargets, type IntakeData } from "../intake";
import type { NutritionPlan, TrainingPlan } from "./schema";

// Voorbeeldconcepten voor de testmodus (AI_MOCK=1), zodat de volledige flow
// zonder API-sleutel lokaal te testen is. Nooit bedoeld voor echte klanten.

const GYM = [
  { name: "Goblet squat", sets: "3", reps: "10-12", rest: "90 sec", notes: "Borst omhoog, knieën in lijn met je tenen." },
  { name: "Roeien met dumbbell", sets: "3", reps: "10 per kant", rest: "60 sec", notes: "" },
  { name: "Dumbbell bench press", sets: "3", reps: "8-10", rest: "90 sec", notes: "" },
  { name: "Roemeense deadlift", sets: "3", reps: "10", rest: "90 sec", notes: "Rug neutraal, beweeg vanuit de heupen." },
  { name: "Plank", sets: "3", reps: "30-45 sec", rest: "45 sec", notes: "" },
];
const HOME = [
  { name: "Squat met eigen gewicht", sets: "3", reps: "15", rest: "60 sec", notes: "" },
  { name: "Push-up (eventueel op knieën)", sets: "3", reps: "8-12", rest: "60 sec", notes: "" },
  { name: "Glute bridge", sets: "3", reps: "15", rest: "45 sec", notes: "" },
  { name: "Lunges", sets: "3", reps: "10 per been", rest: "60 sec", notes: "" },
  { name: "Dead bug", sets: "3", reps: "10 per kant", rest: "45 sec", notes: "" },
];

export function mockTrainingPlan(intake: IntakeData): TrainingPlan {
  const exercises = intake.location === "sportschool" ? GYM : HOME;
  return {
    title: "Voorbeeldschema training (testmodus)",
    summary: `Dit is een automatisch voorbeeld uit de testmodus, gebaseerd op ${intake.trainingDays} trainingen van ${intake.sessionMinutes} minuten per week.`,
    durationWeeks: 6,
    daysPerWeek: intake.trainingDays,
    days: Array.from({ length: intake.trainingDays }, (_, i) => ({
      name: `Dag ${i + 1} – Full body`,
      focus: "Kracht en techniek",
      warmup: "5 minuten fietsen of touwtjespringen, daarna mobiliteit voor heupen en schouders.",
      exercises: exercises.map((e, j) => ({ ...e, sets: j === 0 && i > 0 ? "4" : e.sets })),
      cooldown: "5 minuten rustig uitlopen en rekken.",
    })),
    progression: "Lukt het om alle sets met goede techniek af te ronden? Verhoog dan de week erna het gewicht of het aantal herhalingen.",
    tips: ["Noteer je gewichten na elke training.", "Slaap minimaal 7 uur voor optimaal herstel."],
  };
}

export function mockNutritionPlan(intake: IntakeData): NutritionPlan {
  const t = estimateTargets(intake);
  const perMeal = Math.round(t.calories / intake.mealsPerDay);
  const meals = [
    { name: "Ontbijt", time: "07:30", options: [{ title: "Yoghurtbowl", ingredients: "200 g Griekse yoghurt, 40 g havermout, 15 g walnoten, blauwe bessen, theelepel honing", kcal: perMeal, protein: 25 }, { title: "Volkoren boterhammen", ingredients: "2 volkoren boterhammen met hüttenkäse en tomaat", kcal: perMeal, protein: 22 }] },
    { name: "Lunch", time: "12:30", options: [{ title: "Salade met kip", ingredients: "Gemengde sla, 120 g kipfilet, kikkererwten, komkommer, olijfolie", kcal: perMeal, protein: 35 }] },
    { name: "Tussendoor", time: "15:30", options: [{ title: "Fruit en kwark", ingredients: "Appel en 150 g magere kwark", kcal: Math.round(perMeal * 0.6), protein: 18 }] },
    { name: "Avondeten", time: "18:30", options: [{ title: "Zalm met groenten", ingredients: "150 g zalm, 75 g zilvervliesrijst, broccoli", kcal: perMeal, protein: 38 }] },
    { name: "Avondsnack", time: "21:00", options: [{ title: "Eiwitrijke snack", ingredients: "Skyr met kaneel", kcal: Math.round(perMeal * 0.5), protein: 15 }] },
    { name: "Na de training", time: "Na het trainen", options: [{ title: "Herstelshake", ingredients: "Banaan, 30 g whey, 250 ml melk", kcal: Math.round(perMeal * 0.7), protein: 30 }] },
  ];
  return {
    title: "Voorbeeldschema voeding (testmodus)",
    summary: "Dit is een automatisch voorbeeld uit de testmodus met de berekende richtwaarden.",
    targets: { calories: t.calories, protein: t.protein, carbs: t.carbs, fat: t.fat, water: "2-2,5 liter per dag" },
    meals: meals.slice(0, intake.mealsPerDay),
    avoid: ["Controleer altijd de etiketten op allergenen."],
    tips: ["Eet bij elk eetmoment een eiwitbron.", "Bereid je lunch de avond ervoor voor."],
  };
}
