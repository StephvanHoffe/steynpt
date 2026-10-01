import assert from "node:assert/strict";
import { test } from "node:test";
import { estimateTargets, intakeFromFormData, intakeSchema, type IntakeData } from "../intake";
import { findAllergenWarnings } from "./allergens";
import { mockNutritionPlan, mockTrainingPlan } from "./mock";
import { buildPlanPrompt } from "./prompt";
import { emptyNutritionPlan, nutritionPlanSchema, trainingPlanSchema, type NutritionPlan } from "./schema";

const base: IntakeData = intakeSchema.parse({
  wants: ["training", "voeding"],
  goal: "afvallen",
  sex: "vrouw",
  birthYear: 1994,
  heightCm: 170,
  weightKg: "72,5",
  experience: "beginner",
  trainingDays: 3,
  sessionMinutes: 60,
  location: "sportschool",
  activityLevel: "zittend",
  diet: "vegetarisch",
  allergies: ["noten", "lactose"],
  mealsPerDay: 4,
});

test("intake: komma als decimaal en optionele velden", () => {
  assert.equal(base.weightKg, 72.5);
  assert.equal(base.targetWeightKg, undefined);
  assert.equal(base.injuries, undefined);
});

test("intake: FormData met meerdere allergieën", () => {
  const fd = new FormData();
  fd.append("wants", "voeding");
  fd.append("allergies", "ei");
  fd.append("allergies", "soja");
  fd.append("consent", "on");
  fd.append("$ACTION_ID_x", "1");
  const raw = intakeFromFormData(fd);
  assert.deepEqual(raw.allergies, ["ei", "soja"]);
  assert.deepEqual(raw.wants, ["voeding"]);
  assert.equal("consent" in raw, false);
  assert.equal(Object.keys(raw).some((k) => k.startsWith("$")), false);
});

test("intake: validatiefouten", () => {
  const result = intakeSchema.safeParse({ ...base, wants: [], birthYear: 2020, trainingDays: 9 });
  assert.equal(result.success, false);
});

test("richtwaarden: tekort bij afvallen, nooit onder rustmetabolisme", () => {
  const t = estimateTargets(base, 2026);
  assert.ok(t.calories < t.maintenance, "tekort bij afvallen");
  assert.ok(t.calories >= t.bmr, "niet onder BMR");
  assert.ok(t.calories >= 1200);
  assert.equal(t.protein, 130); // 1.8 g/kg × 72.5 kg, afgerond op 5
  const kcal = t.protein * 4 + t.carbs * 4 + t.fat * 9;
  assert.ok(Math.abs(kcal - t.calories) < 60, "macro's tellen op tot de energie");
  const bulk = estimateTargets({ ...base, goal: "spieropbouw", sex: "man" }, 2026);
  assert.ok(bulk.calories > bulk.maintenance);
});

test("prompt: bevat allergieën en richtwaarden, geen persoonsgegevens", () => {
  const { system, user } = buildPlanPrompt("voeding", { ...base, medical: "Hoge bloeddruk" }, "Minder koolhydraten in de avond");
  assert.match(user, /Noten, Lactose/);
  assert.match(user, /Doel energie: \d+ kcal/);
  assert.match(user, /Hoge bloeddruk/);
  assert.match(user, /Minder koolhydraten in de avond/);
  assert.match(system, /harde eisen/);
  assert.doesNotMatch(user, /Trainingslocatie/, "trainingsvragen niet in voedingsprompt");
  const training = buildPlanPrompt("training", base);
  assert.match(training.user, /Trainingen per week: 3×/);
  assert.doesNotMatch(training.user, /Allergieën/);
});

const plan = (ingredients: string[], extra: Partial<NutritionPlan> = {}): NutritionPlan => ({
  ...emptyNutritionPlan(),
  meals: [{ name: "Ontbijt", time: "08:00", options: ingredients.map((i, n) => ({ title: `Optie ${n + 1}`, ingredients: i, kcal: 400, protein: 20 })) }],
  ...extra,
});

test("allergenen: vindt noten, lactose en vlees bij vegetariër", () => {
  const warnings = findAllergenWarnings(
    plan(["Havermout met walnoten", "Volkoren brood met kipfilet", "Kwark met fruit"]),
    { allergies: ["noten", "lactose"], diet: "vegetarisch" },
  );
  assert.deepEqual(
    warnings.map((w) => `${w.where}|${w.reason}|${w.term}`),
    ["Ontbijt › Optie 1|allergie: noten|walnoten", "Ontbijt › Optie 2|eetstijl: vegetarisch|kipfilet", "Ontbijt › Optie 3|allergie: lactose|kwark"],
  );
});

test("allergenen: geen valse meldingen", () => {
  const warnings = findAllergenWarnings(
    plan([
      "Lactosevrije kwark met eiwitpoeder en havermelk",
      "Glutenvrij brood zonder noten",
      "Vegetarische kipstukjes met rijst",
      "Kokosyoghurt en een eigen recept",
    ]),
    { allergies: ["lactose", "noten", "ei"], diet: "vegetarisch" },
  );
  assert.deepEqual(warnings, []);
});

test("allergenen: lijst 'avoid' wordt niet gecontroleerd, 'zonder' geldt alleen direct ervoor", () => {
  assert.deepEqual(findAllergenWarnings(plan([], { avoid: ["Pinda's en noten"] }), { allergies: ["pinda", "noten"], diet: "alles" }), []);
  const w = findAllergenWarnings(plan(["Kwark zonder suiker, met noten"]), { allergies: ["noten"], diet: "alles" });
  assert.equal(w.length, 1);
});

test("allergenen: woorden die alleen op een trefwoord lijken", () => {
  const context = { allergies: ["lactose", "noten"] as IntakeData["allergies"], diet: "vegetarisch" as const };
  assert.deepEqual(findAllergenWarnings(plan(["2 volkoren boterhammen met hummus", "Speculaas en een snufje nootmuskaat"]), context), []);
  assert.equal(findAllergenWarnings(plan(["Hamburger met friet"]), { allergies: [], diet: "halal" }).length, 0);
  assert.equal(findAllergenWarnings(plan(["Broodje met spek"]), { allergies: [], diet: "halal" }).length, 1);
  assert.equal(findAllergenWarnings(plan(["Roomboter op brood"]), { allergies: ["melk"], diet: "alles" }).length, 1);
  assert.equal(findAllergenWarnings(plan(["2 volkoren boterhammen"]), { allergies: ["gluten"], diet: "alles" })[0]?.term, "boterhammen");
});

test("allergenen: ei als los woord, niet in eiwit", () => {
  assert.equal(findAllergenWarnings(plan(["Eiwitshake"]), { allergies: ["ei"], diet: "alles" }).length, 0);
  assert.equal(findAllergenWarnings(plan(["2 gekookte eieren"]), { allergies: ["ei"], diet: "alles" }).length, 1);
  assert.equal(findAllergenWarnings(plan(["1 ei met spinazie"]), { allergies: [], diet: "veganistisch" }).length, 1);
});

test("testmodus-concepten voldoen aan de schema's", () => {
  const training = trainingPlanSchema.parse(mockTrainingPlan(base));
  assert.equal(training.days.length, 3);
  const nutrition = nutritionPlanSchema.parse(mockNutritionPlan(base));
  assert.equal(nutrition.meals.length, 4);
  assert.ok(findAllergenWarnings(nutrition, base).length > 0, "voorbeeld bevat bewust een conflict (walnoten)");
});
