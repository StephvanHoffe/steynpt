import { z } from "zod";

// Vaste structuur van trainings- en voedingsschema's. Dezelfde schema's worden
// gebruikt als structured output voor de AI én voor het valideren van Steyns bewerkingen.
// Alle velden zijn verplicht (eis van structured outputs); lege tekst mag. Er zitten
// bewust geen lengte- of getalgrenzen in: die controleert de API niet en een te lang
// AI-antwoord zou dan alsnog afgekeurd worden. De totale omvang begrenst savePlan.

const text = () => z.string();

export const exerciseSchema = z.object({
  name: text().describe("Naam van de oefening, in het Nederlands of de gangbare Engelse term"),
  sets: text().describe("Aantal sets, bijv. '3' of '3-4'"),
  reps: text().describe("Herhalingen of duur, bijv. '8-10', '12 per kant' of '30 sec'"),
  rest: text().describe("Rust tussen de sets, bijv. '90 sec'"),
  notes: text().describe("Korte uitvoeringstips of een alternatief bij blessures; leeg als niet nodig"),
});

export const trainingDaySchema = z.object({
  name: text().describe("Bijv. 'Dag 1 – Bovenlichaam'"),
  focus: text(),
  warmup: text(),
  exercises: z.array(exerciseSchema),
  cooldown: text(),
});

export const trainingPlanSchema = z.object({
  title: text(),
  summary: text().describe("Persoonlijke uitleg aan de klant over de opzet van het schema, 2-4 zinnen"),
  durationWeeks: z.number().describe("Hoeveel weken dit schema gevolgd wordt voor een evaluatie"),
  daysPerWeek: z.number(),
  days: z.array(trainingDaySchema),
  progression: text().describe("Hoe de klant week op week progressie maakt"),
  tips: z.array(text()),
});

export const mealOptionSchema = z.object({
  title: text(),
  ingredients: text().describe("Ingrediënten met hoeveelheden"),
  kcal: z.number(),
  protein: z.number().describe("Gram eiwit"),
});

export const mealSchema = z.object({
  name: text().describe("Bijv. 'Ontbijt', 'Lunch', 'Tussendoor'"),
  time: text().describe("Richttijd, bijv. '07:30' of 'Na de training'"),
  options: z.array(mealOptionSchema),
});

export const nutritionPlanSchema = z.object({
  title: text(),
  summary: text().describe("Persoonlijke uitleg aan de klant over de aanpak, 2-4 zinnen"),
  targets: z.object({
    calories: z.number(),
    protein: z.number(),
    carbs: z.number(),
    fat: z.number(),
    water: text().describe("Bijv. '2-2,5 liter per dag'"),
  }),
  meals: z.array(mealSchema),
  avoid: z.array(text()).describe("Wat de klant moet vermijden, o.a. op basis van allergieën en eetstijl"),
  tips: z.array(text()),
});

export type Exercise = z.infer<typeof exerciseSchema>;
export type TrainingDay = z.infer<typeof trainingDaySchema>;
export type TrainingPlan = z.infer<typeof trainingPlanSchema>;
export type MealOption = z.infer<typeof mealOptionSchema>;
export type Meal = z.infer<typeof mealSchema>;
export type NutritionPlan = z.infer<typeof nutritionPlanSchema>;
export type PlanContent = TrainingPlan | NutritionPlan;

export function planSchemaFor(type: "training" | "voeding") {
  return type === "training" ? trainingPlanSchema : nutritionPlanSchema;
}

export const emptyExercise = (): Exercise => ({ name: "", sets: "3", reps: "10", rest: "90 sec", notes: "" });
export const emptyTrainingDay = (n: number): TrainingDay => ({
  name: `Dag ${n}`,
  focus: "",
  warmup: "",
  exercises: [emptyExercise()],
  cooldown: "",
});
export const emptyMealOption = (): MealOption => ({ title: "", ingredients: "", kcal: 0, protein: 0 });
export const emptyMeal = (): Meal => ({ name: "", time: "", options: [emptyMealOption()] });

export function emptyTrainingPlan(daysPerWeek = 3): TrainingPlan {
  return {
    title: "Trainingsschema",
    summary: "",
    durationWeeks: 6,
    daysPerWeek,
    days: Array.from({ length: daysPerWeek }, (_, i) => emptyTrainingDay(i + 1)),
    progression: "",
    tips: [],
  };
}

export function emptyNutritionPlan(targets?: { calories: number; protein: number; carbs: number; fat: number }): NutritionPlan {
  return {
    title: "Voedingsschema",
    summary: "",
    targets: { calories: 0, protein: 0, carbs: 0, fat: 0, water: "2-2,5 liter per dag", ...targets },
    meals: ["Ontbijt", "Lunch", "Avondeten"].map((name) => ({ ...emptyMeal(), name })),
    avoid: [],
    tips: [],
  };
}
